<?php

namespace PrismArea\trait;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

trait AreaSubFlagItemTrait
{
    /**
     * Returns the item that represents this area flag.
     *
     * @return Item
     */
    public function getItem(): Item
    {
        return match ($this) {
            self::PLAYER_USE_ITEMS_ENDER_PEARL => VanillaItems::ENDER_PEARL(),
            self::PLAYER_USE_ITEMS_SNOWBALL => VanillaItems::SNOWBALL(),
            self::PLAYER_USE_ITEMS_EGG => VanillaItems::EGG(),
            self::PLAYER_USE_ITEMS_POTIONS => VanillaItems::POTION(),
            self::PLAYER_USE_ITEMS_SPLASH_POTIONS => VanillaItems::SPLASH_POTION(),

            self::PLAYER_CONTAINERS_CHEST => VanillaBlocks::CHEST()->asItem(),
            self::PLAYER_CONTAINERS_ENDER_CHEST => VanillaBlocks::ENDER_CHEST()->asItem(),
            self::PLAYER_CONTAINERS_FURNACE => VanillaBlocks::FURNACE()->asItem(),
            self::PLAYER_CONTAINERS_BARREL => VanillaBlocks::BARREL()->asItem(),
            self::PLAYER_CONTAINERS_HOPPER => VanillaBlocks::HOPPER()->asItem(),
            self::PLAYER_CONTAINERS_BREWING_STAND => VanillaBlocks::BREWING_STAND()->asItem(),
            self::PLAYER_CONTAINERS_SHULKER_BOX => VanillaBlocks::SHULKER_BOX()->asItem(),

            self::PLAYER_INTERACT_AXE => VanillaItems::GOLDEN_AXE(),
            self::PLAYER_INTERACT_SHOVEL => VanillaItems::GOLDEN_SHOVEL(),
            self::PLAYER_INTERACT_HOE => VanillaItems::GOLDEN_HOE(),
            self::PLAYER_INTERACT_BUCKET => VanillaItems::WATER_BUCKET(),
            self::PLAYER_INTERACT_FLINT_AND_STEEL => VanillaItems::FLINT_AND_STEEL(),

            self::WORLD_DAMAGE_FALL => VanillaItems::FEATHER(),

            self::BLOCK_FORM => VanillaBlocks::OBSIDIAN()->asItem(),
            self::BLOCK_EXPLOSION => VanillaBlocks::TNT()->asItem(),
            self::BLOCK_BURN => VanillaBlocks::FIRE()->asItem(),
            self::BLOCK_SPREAD => VanillaBlocks::LAVA()->asItem(),
            self::BLOCK_LEAVES_DECAY => VanillaBlocks::OAK_LEAVES()->asItem(),
            self::BLOCK_GROW => VanillaBlocks::OAK_SAPLING()->asItem(),
            self::BLOCK_MELT => VanillaBlocks::ICE()->asItem(),
            self::BLOCK_SIGN_CHANGE => VanillaBlocks::OAK_SIGN()->asItem(),
        };
    }
}