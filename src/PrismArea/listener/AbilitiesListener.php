<?php

namespace PrismArea\listener;

use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use PrismAPI\item\ItemFactory;
use PrismAPI\types\ItemLockMode;
use PrismArea\Loader;
use PrismArea\session\Session;
use PrismArea\session\SessionManager;
use PrismArea\timings\TimingsManager;
use PrismArea\types\AbilitiesLayer;

class AbilitiesListener
{
    /**
     * AbilitiesListener constructor.
     *
     * This listener handles player abilities by listening to incoming packets.
     * It checks if the player is in an area and recalculates abilities accordingly.
     *
     * @param Loader $loader The plugin loader instance.
     */
    public function __construct(
        protected readonly Loader $loader,
        private readonly int      $tick,
    )
    {
        try {
            $this->loader->getServer()->getPluginManager()->registerEvent(
                DataPacketReceiveEvent::class,
                $this->handleReceive(...),
                EventPriority::MONITOR,
                $this->loader,
            );
        } catch (\Throwable $e) {
            $this->loader->getLogger()->error("Failed to register DataPacketReceiveEvent listener: " . $e->getMessage());
        }

        try {
            $this->loader->getServer()->getPluginManager()->registerEvent(
                DataPacketSendEvent::class,
                $this->handleSend(...),
                EventPriority::MONITOR,
                $this->loader,
            );
        } catch (\Throwable $e) {
            $this->loader->getLogger()->error("Failed to register DataPacketSendEvent listener: " . $e->getMessage());
        }
    }

    public function handleSend(DataPacketSendEvent $ev): void
    {
        $packets = $ev->getPackets();
        $targets = $ev->getTargets();

        // Check if the event contains exactly one packet and one target
        if (count($packets) !== 1 || count($targets) !== 1) {
            return;
        }

        // Extract the packet and target
        $pk = array_shift($packets);
        $origin = array_shift($targets);

        // Check if the origin is a player
        $player = $origin->getPlayer();
        if ($player === null) {
            return;
        }

        // Get or create a session for the player
        $session = SessionManager::getInstance()->getOrCreate($player);
        if ($session->isClosed()) {
            return;
        }

        if ($pk instanceof InventoryContentPacket) {
            if ($player->isCreative(true)) {
                return;
            }

            // If the packet is an InventoryContentPacket, process it
            // Its a hack for cancel drop items
            $this->processInventoryContent($session, $player, $pk);
            return;
        }

        // Check if the packet is an UpdateAbilitiesPacket
        if (!$pk instanceof UpdateAbilitiesPacket) {
            return;
        }

        // Check if the origin is a player
        $data = $pk->getData();
        $layers = $data->getAbilityLayers();

        // If the player is not in an area, we can skip further processing
        foreach ($layers as $layer) {
            // Skip if the layer is not the base layer
            if ($layer->getLayerId() !== AbilitiesLayer::LAYER_BASE) {
                continue;
            }

            // If the layer is the same as the session's abilities, we can skip further processing
            if ($layer === $session->getAbilities()) {
                continue;
            }

            // If the player is in an area, we need to recalculate abilities
            /** @var UpdateAbilitiesPacket|null $updateAbilitiesPacket */
            $updateAbilitiesPacket = null;
            $session->recalculateAbilities($updateAbilitiesPacket);

            // If the recalculated abilities packet is not null, we update the session's abilities
            if ($updateAbilitiesPacket !== null) {
                // Merge the new abilities with the existing ones
                $newAbilities = array_merge(
                    $data->getAbilityLayers(),
                    $updateAbilitiesPacket->getData()->getAbilityLayers(),
                );
                $session->setAbilities($newAbilities);

                $reflectionClass = new \ReflectionClass(AbilitiesData::class);
                $abilityLayersProperty = $reflectionClass->getProperty('abilityLayers');
                $abilityLayersProperty->setValue($data, $newAbilities);

                break; // Exit the loop after processing the base layer
            }
        }
    }

    /**
     * Processes the InventoryContentPacket for the player.
     *
     * This method is a placeholder for future implementation.
     *
     * @param Session $session The session of the player.
     * @param Player $player The player whose inventory content is being processed.
     * @param InventoryContentPacket $pk The packet containing inventory content.
     * @return void
     */
    private function processInventoryContent(Session $session, Player $player, InventoryContentPacket $pk): void
    {
        $contents = $player->getInventory()->getContents(true);
        if (count($contents) !== count($pk->items)) {
            // If the number of items in the inventory does not match the number of items in the packet, we skip processing
            return;
        }

        $drop = $session->getAbilities()[AbilitiesLayer::ABILITY_DROP] ?? false;
        if (!$drop) {
            // If the player is allowed to drop items, we skip processing
            return;
        }

        $timings = TimingsManager::getInstance()->getInventoryUpdate();
        try {
            $timings->startTiming(); // Start timing the inventory update

            $converter = TypeConverter::getInstance();
            for ($i = 0; $i < count($pk->items); $i++) {
                $itemWrapper = $pk->items[$i];

                $item = TypeConverter::getInstance()->netItemStackToCore($itemWrapper->getItemStack());
                $pk->items[$i] = new ItemStackWrapper($itemWrapper->getStackId(), $converter->coreItemStackToNet(ItemFactory::LOCK($item, ItemLockMode::FULL_INVENTORY)));
            }
        } finally {
            $timings->stopTiming(); // Stop timing the inventory update
        }
    }

    /**
     * Handles incoming packets and checks for player abilities.
     *
     * @param DataPacketReceiveEvent $ev The event containing the received packet.
     * @return void
     */
    public function handleReceive(DataPacketReceiveEvent $ev): void
    {
        $pk = $ev->getPacket();
        $origin = $ev->getOrigin();

        // Check if the origin is a player
        $player = $origin->getPlayer();
        if ($player === null) {
            return;
        }

        // Get or create a session for the player
        $session = SessionManager::getInstance()->getOrCreate($player);
        if ($session->isClosed()) {
            return;
        }

        // Check if the packet is a SetLocalPlayerAsInitializedPacket
        if ($pk instanceof SetLocalPlayerAsInitializedPacket) {
            // Remove yellow arrow in bottom_right of item
            $origin->sendDataPacket(GameRulesChangedPacket::create(["showtags" => new BoolGameRule(false, false)]));
            $player->addAttachment($this->loader, "prism.flag.*", true);
            return;
        }

        // Check if the packet is a PlayerAuthInputPacket
        if (!$pk instanceof PlayerAuthInputPacket) {
            return;
        }

        // PocketMine-MP wrong implementation for EyePos (its not always 1.62)
        if ($pk->getPosition()->distanceSquared($player->getEyePos()) < 0.0001) {
            // If the player hasn't moved, we skip processing
            return;
        }

        if ($pk->getTick() % $this->tick !== 0) {
            return; // Process only every $this->tick ticks
        }

        $timings = TimingsManager::getInstance()->getCalculatingAbilities();
        $timings->startTiming(); // Start timing the ability calculation
        try {
            // Check if the player is in an area
            // and recalculate abilities if necessary
            $updateAbilitiesPacket = null;
            $session->recalculateAbilities($updateAbilitiesPacket);

            if ($updateAbilitiesPacket !== null) {
                $origin->sendDataPacket($updateAbilitiesPacket);
            }
        } finally {
            $timings->stopTiming(); // Stop timing the ability calculation
        }
    }
}