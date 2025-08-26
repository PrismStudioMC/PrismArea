<?php

namespace PrismArea\listener;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\tile\Barrel;
use pocketmine\block\tile\BrewingStand;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\EnderChest;
use pocketmine\block\tile\Furnace;
use pocketmine\block\tile\Hopper;
use pocketmine\block\tile\ShulkerBox;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerEmoteEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerToggleSwimEvent;
use pocketmine\item\Axe;
use pocketmine\item\Bucket;
use pocketmine\item\Egg;
use pocketmine\item\EnderPearl;
use pocketmine\item\FlintSteel;
use pocketmine\item\Hoe;
use pocketmine\item\Potion;
use pocketmine\item\Shovel;
use pocketmine\item\Snowball;
use pocketmine\item\SplashPotion;
use pocketmine\player\Player;
use PrismArea\area\AreaManager;
use PrismArea\Loader;
use PrismArea\session\SessionManager;
use PrismArea\types\AreaFlag;
use PrismArea\types\AreaSubFlag;
use PrismArea\types\Translatable;

class PlayerListener implements Listener
{
    protected SessionManager $sessionManager;

    /**
     * PlayerListener constructor.
     *
     * @param Loader $loader
     * @param AreaManager $areaManager
     */
    public function __construct(
        protected readonly Loader      $loader,
        protected readonly AreaManager $areaManager
    )
    {
        $this->sessionManager = SessionManager::getInstance();
    }

    /**
     * @param PlayerInteractEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handlePlayerInteract(PlayerInteractEvent $ev): void
    {
        $player = $ev->getPlayer();
        $item = $ev->getItem();
        $block = $ev->getBlock();
        $action = $ev->getAction();

        $area = $this->areaManager->find($block->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the player can interact with the area
        $flag = AreaFlag::fromString(
            $action === PlayerInteractEvent::LEFT_CLICK_BLOCK ? 'left_click' : 'right_click'
        );
        if (!$area->can($flag, $player, $block->getPosition())) {
            $this->sessionManager->getOrCreate($player)
                ->sendMessage(Translatable::AREA_INTERACT_DENIED);
            $ev->cancel();
            return; // Player cannot interact in this area
        }

        switch ($action) {
            case PlayerInteractEvent::LEFT_CLICK_BLOCK:
            {
                // Handle left click interaction
                if ($area->can(AreaFlag::PLAYER_BREAK, $player, $block->getPosition())) {
                    return; // Player can perform emotes, nothing to do
                }

                $ev->cancel();
                break;
            }
            case PlayerInteractEvent::RIGHT_CLICK_BLOCK:
            {
                // Handle right click interaction
                $placedBlock = $item->getBlock();
                if (!$placedBlock instanceof Air && !$area->can(AreaFlag::PLAYER_BUILD, $player, $block->getPosition())) {
                    $this->sessionManager->getOrCreate($player)
                        ->sendMessage(Translatable::PLAYER_PLACE_DENIED);
                    $ev->cancel();
                    return;
                }

                // Check if the area allows player interaction
                if (!$area->can(AreaFlag::PLAYER_INTERACT, $player, $block->getPosition())) {
                    $this->sessionManager->getOrCreate($player)
                        ->sendMessage(Translatable::AREA_INTERACT_DENIED);
                    $ev->cancel();
                    return;
                }

                $map = [
                    Axe::class => AreaSubFlag::PLAYER_INTERACT_AXE,
                    Shovel::class => AreaSubFlag::PLAYER_INTERACT_SHOVEL,
                    Hoe::class => AreaSubFlag::PLAYER_INTERACT_HOE,
                    Bucket::class => AreaSubFlag::PLAYER_INTERACT_BUCKET,
                    FlintSteel::class => AreaSubFlag::PLAYER_INTERACT_FLINT_AND_STEEL,
                ];

                // Iterate through the map to check if the item matches any class
                foreach ($map as $class => $subFlag) {
                    if ($item instanceof $class) {
                        if (!$area->can($subFlag, $player, $block->getPosition())) {
                            $this->sessionManager->getOrCreate($player)
                                ->sendMessage(Translatable::AREA_INTERACT_DENIED);
                            $ev->cancel();
                        }
                        return;
                    }
                }

                // Handle right click interaction
                $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
                if ($tile === null) {
                    return; // No tile found, nothing to do
                }

                if (!$tile instanceof Container) {
                    return; // Tile is not a container, nothing to do
                }

                if (!$area->can(AreaFlag::PLAYER_CONTAINERS, $player, $block->getPosition())) {
                    $this->sessionManager->getOrCreate($player)
                        ->sendMessage(Translatable::PLAYER_CONTAINERS_DENIED, $block->getName());
                    $ev->cancel();
                    return;
                }

                $map = [
                    Chest::class => AreaSubFlag::PLAYER_CONTAINERS_CHEST,
                    EnderChest::class => AreaSubFlag::PLAYER_CONTAINERS_ENDER_CHEST,
                    Furnace::class => AreaSubFlag::PLAYER_CONTAINERS_FURNACE,
                    Barrel::class => AreaSubFlag::PLAYER_CONTAINERS_BARREL,
                    Hopper::class => AreaSubFlag::PLAYER_CONTAINERS_HOPPER,
                    BrewingStand::class => AreaSubFlag::PLAYER_CONTAINERS_BREWING_STAND,
                    ShulkerBox::class => AreaSubFlag::PLAYER_CONTAINERS_SHULKER_BOX,
                ];

                // Iterate through the map to check if the item matches any class
                foreach ($map as $class => $subFlag) {
                    if ($item instanceof $class) {
                        if (!$area->can($subFlag, $player, $player->getPosition())) {
                            $this->sessionManager->getOrCreate($player)
                                ->sendMessage(Translatable::PLAYER_CONTAINERS_DENIED, $block->getName());
                            $ev->cancel();
                        }
                        return;
                    }
                }
                break;
            }
            default: // Ignore other actions
                break;
        }
    }

    /**
     * @param BlockBreakEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockBreak(BlockBreakEvent $ev): void
    {
        $player = $ev->getPlayer();
        $block = $ev->getBlock();

        $area = $this->areaManager->find($block->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        if ($area->can(AreaFlag::PLAYER_BREAK, $player, $block->getPosition())) {
            return;
        }

        $this->sessionManager->getOrCreate($player)
            ->sendMessage(Translatable::PLAYER_BREAK_DENIED);
        $ev->cancel();
    }

    /**
     * @param BlockPlaceEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockPlace(BlockPlaceEvent $ev): void
    {
        $player = $ev->getPlayer();
        $transaction = $ev->getTransaction();

        /**
         * @var mixed $k index of the transaction
         * @var Block $block
         */
        foreach ($transaction->getBlocks() as $_ => [, , , $block]) {
            $area = $this->areaManager->find($block->getPosition());
            if ($area === null) {
                continue; // No area found, nothing to do
            }

            if (!$area->can(AreaFlag::PLAYER_BUILD, $player, $block->getPosition())) {
                $this->sessionManager->getOrCreate($player)
                    ->sendMessage(Translatable::PLAYER_PLACE_DENIED);
                $ev->cancel();
                return; // Player cannot build in this area
            }
        }
    }

    /**
     * @param PlayerItemUseEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handlePlayerItemUse(PlayerItemUseEvent $ev): void
    {
        $player = $ev->getPlayer();
        $item = $ev->getItem();

        // Check if the player is in an area
        $area = $this->areaManager->find($player->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the player can use items in the area
        if (!$area->can(AreaFlag::PLAYER_USE_ITEMS, $player, $player->getPosition())) {
            $this->sessionManager->getOrCreate($player)
                ->sendMessage(Translatable::PLAYER_USE_ITEMS_DENIED, $item->getName());
            $ev->cancel();
            return;
        }

        // Check for specific item types and their sub-flags
        $map = [
            EnderPearl::class => AreaSubFlag::PLAYER_USE_ITEMS_ENDER_PEARL,
            Snowball::class => AreaSubFlag::PLAYER_USE_ITEMS_SNOWBALL,
            Egg::class => AreaSubFlag::PLAYER_USE_ITEMS_EGG,
            Potion::class => AreaSubFlag::PLAYER_USE_ITEMS_POTIONS,
            SplashPotion::class => AreaSubFlag::PLAYER_USE_ITEMS_POTIONS,
        ];

        // Iterate through the map to check if the item matches any class
        foreach ($map as $class => $subFlag) {
            if ($item instanceof $class) {
                if (!$area->can($subFlag, $player, $player->getPosition())) {
                    $this->sessionManager->getOrCreate($player)
                        ->sendMessage(Translatable::PLAYER_USE_ITEMS_DENIED, $item->getName());
                    $ev->cancel();
                }
                return;
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handlePlayerDrop(PlayerDropItemEvent $ev): void
    {
        $player = $ev->getPlayer();

        // Check if the player is in an area
        $area = $this->areaManager->find($player->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the player can drop items in the area
        if ($area->can(AreaFlag::PLAYER_DROP, $player, $player->getPosition())) {
            return; // Player can drop items, nothing to do
        }

        $this->sessionManager->getOrCreate($player)
            ->sendMessage(Translatable::PLAYER_DROP_DENIED, $ev->getItem()->getName());
        $ev->cancel();
    }

    /**
     * @param EntityItemPickupEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleEntityItemPickup(EntityItemPickupEvent $ev): void
    {
        $entity = $ev->getEntity();

        // Check if the entity is a player
        if (!$entity instanceof Player) {
            return; // Only handle player pickups
        }

        // Check if the player is in an area
        $area = $this->areaManager->find($entity->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the player can pick up items in the area
        if ($area->can(AreaFlag::PLAYER_PICKUP, $entity, $entity->getPosition())) {
            return; // Player can pick up items, nothing to do
        }

        $ev->cancel();
    }

    /**
     * @param PlayerBucketEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handlePlayerBucket(PlayerBucketEvent $ev): void
    {
        $player = $ev->getPlayer();
        $face = $ev->getBlockFace();
        $blockClicked = $ev->getBlockClicked();
        $blockClickedPos = $blockClicked->getPosition();

        // Check if the player is in an area
        $block = $blockClickedPos->getWorld()->getBlock($blockClickedPos->getSide($face));
        $area = $this->areaManager->find($block->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the player can use buckets in the area
        if ($area->can(AreaSubFlag::PLAYER_INTERACT_BUCKET, $player, $block->getPosition())) {
            return; // Player can use buckets, nothing to do
        }

        $this->sessionManager->getOrCreate($player)
            ->sendMessage(Translatable::AREA_INTERACT_DENIED);
        $ev->cancel();
    }

    /**
     * @param PlayerEmoteEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handlePlayerEmote(PlayerEmoteEvent $ev): void
    {
        $player = $ev->getPlayer();

        // Check if the player is in an area
        $area = $this->areaManager->find($player->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the player can perform emotes in the area
        if ($area->can(AreaFlag::PLAYER_EMOTES, $player, $player->getPosition())) {
            return;
        }

        $ev->cancel();
    }

    /**
     * @param PlayerToggleSwimEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handlePlayerSwim(PlayerToggleSwimEvent $ev): void
    {
        $player = $ev->getPlayer();

        // Check if the player is swimming
        if (!$ev->isSwimming()) {
            return;
        }

        // Check if the player is in an area
        $area = $this->areaManager->find($player->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the player can perform emotes in the area
        if ($area->can(AreaFlag::PLAYER_EMOTE, $player, $player->getPosition())) {
            return;
        }

        $ev->cancel();
    }
}