<?php

namespace PrismArea\session;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\world\clientblock\ClientSideBlockManager;
use platz1de\EasyEdit\world\clientblock\StructureBlockWindow;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer as Layer;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\utils\ObjectSet;
use PrismArea\area\Area;
use PrismArea\area\AreaManager;
use PrismArea\events\PlayerRecalculateAbilitiesEvent;
use PrismArea\lang\Lang;
use PrismArea\lang\LangManager;
use PrismArea\Loader;
use PrismArea\types\AbilitiesLayer;
use PrismArea\types\AreaFlag;
use PrismArea\types\Translatable;

class Session
{
    private Lang $lang;
    private bool $closed = false;
    private bool $sendMessages = true;
    private ?int $structureBlockWindow = null;

    /**
     * @param bool[] $boolAbilities
     * @phpstan-param array<self::ABILITY_*, bool> $boolAbilities
     */
    private array $abilities = [];

    public function __construct(
        private Player $player,
    ) {
        if (!$this->player->isConnected()) {
            throw new \InvalidArgumentException("Player must be connected to create a session.");
        }

        $origin = $this->player->getNetworkSession();

        $this->lang = LangManager::getInstance()->getLang($this->player->getLocale());
        $this->sendMessages = Loader::getInstance()->getConfig()->get("use-messages", true);

        $reflectionClass = new \ReflectionClass(NetworkSession::class);
        $disposeHooksProperty = $reflectionClass->getProperty("disposeHooks");

        /**
         * @var \Closure[]|ObjectSet $disposeHooks
         * @phpstan-var ObjectSet<\Closure() : void> $disposeHooks
         */
        $disposeHooks = $disposeHooksProperty->getValue($origin);
        $disposeHooks->add(function (): void {
            $this->closed = true;
            SessionManager::getInstance()->close($this->player);
        });
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return Lang
     */
    public function getLang(): Lang
    {
        return $this->lang;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * @return array
     */
    public function getAbilities(): array
    {
        return $this->abilities;
    }

    /**
     * @param array $abilities
     */
    public function setAbilities(array $abilities): void
    {
        $this->abilities = $abilities;
    }

    /**
     * @param Translatable $k
     * @param mixed ...$args
     * @return void
     */
    public function sendMessage(Translatable $k, mixed ...$args): void
    {
        if (!$this->sendMessages) {
            return;
        }

        // Check if the session is closed before sending a message
        if ($this->closed) {
            return; // Do not send messages if the session is closed
        }

        $this->player->sendMessage($this->getLang()->parse(
            $k,
            ...$args
        ));
    }

    /**
     * @param Area $area
     * @return void
     */
    public function visualize(Area $area): void
    {
        $this->unvisualize();
        $this->structureBlockWindow = ClientSideBlockManager::registerBlock($this->getPlayer()->getName(), new StructureBlockWindow(
            $this->getPlayer(),
            new BlockVector($area->getAABB()->minX, $area->getAABB()->minY, $area->getAABB()->minZ),
            new BlockVector($area->getAABB()->maxX, $area->getAABB()->maxY, $area->getAABB()->maxZ),
        ));
    }

    /**
     * @return void
     */
    public function unvisualize(): void
    {
        if (is_null($this->structureBlockWindow)) {
            return;
        }

        ClientSideBlockManager::unregisterBlock($this->getPlayer(), $this->structureBlockWindow);
    }

    /**
     * Recalculates the abilities for the session.
     *
     * This method is currently a placeholder and does not perform any actions.
     * It can be extended in the future to implement permission recalculation logic.
     * @param UpdateAbilitiesPacket|null $pk
     * @return void
     */
    public function recalculateAbilities(UpdateAbilitiesPacket &$pk = null): void
    {
        $player = $this->getPlayer();

        $newAbilities = [];
        $area = AreaManager::getInstance()->find($player->getPosition());
        if ($area !== null) {
            $newAbilities = [
                AbilitiesLayer::ABILITY_BUILD => $area->can(AreaFlag::PLAYER_BUILD, $player),
                AbilitiesLayer::ABILITY_MINE => $area->can(AreaFlag::PLAYER_BREAK, $player),
                AbilitiesLayer::ABILITY_OPEN_CONTAINERS => $area->can(AreaFlag::PLAYER_CONTAINERS, $player),
                AbilitiesLayer::ABILITY_ATTACK_PLAYERS => $area->can(AreaFlag::WORLD_ATTACK_PLAYERS, $player),
                AbilitiesLayer::ABILITY_ATTACK_MOBS => $area->can(AreaFlag::WORLD_ATTACK_MOBS, $player),
                AbilitiesLayer::RIGHT_CLICK => $area->can(AreaFlag::RIGHT_CLICK, $player),
                AbilitiesLayer::ABILITY_DROP => !$area->can(AreaFlag::PLAYER_DROP, $player), // hack for drop items
                AbilitiesLayer::ABILITY_OPERATOR => false, // If this is set to true, the player will have operator permissions in the area
            ];
        }

        $ev = new PlayerRecalculateAbilitiesEvent($player, $this->abilities, $newAbilities);
        $ev->call();

        // If the new abilities are the same as the current abilities, we do not need to update anything
        if ($ev->getNewAbilities() === $ev->getPrevAbilities()) {
            return;
        }

        $prev = $ev->getPrevAbilities()[AbilitiesLayer::ABILITY_DROP] ?? false;
        $new = $ev->getNewAbilities()[AbilitiesLayer::ABILITY_DROP] ?? false;

        // Set the new abilities
        $this->abilities = $ev->getNewAbilities();

        // Check if the drop ability changed
        $needSync = $prev !== $new;
        if ($needSync) { // If the drop ability changed, we need to sync the inventory
            $player->getNetworkSession()->getInvManager()?->syncAll();
        }

        // If there are no new abilities, we just sync the current abilities
        if (empty($ev->getNewAbilities())) {
            $player->getNetworkSession()->syncAbilities($player);
            return;
        }

        $pk = UpdateAbilitiesPacket::create(new AbilitiesData(
            $player->hasPermission(DefaultPermissions::ROOT_OPERATOR) ? CommandPermissions::OPERATOR : CommandPermissions::NORMAL,
            PlayerPermissions::MEMBER,
            $player->getId(),
            [new Layer(AbilitiesLayer::LAYER_BASE, $ev->getNewAbilities(), $player->getFlightSpeedMultiplier(), 1, 0.1)]
        ));
    }
}
