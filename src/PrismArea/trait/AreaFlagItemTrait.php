<?php

namespace PrismArea\trait;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

trait AreaFlagItemTrait
{
    /**
     * Returns the item that represents this area flag.
     *
     * @return Item
     */
    public function getItem(): Item
    {
        return match ($this) {
            self::LEFT_CLICK => VanillaItems::GOLDEN_AXE(),
            self::RIGHT_CLICK => VanillaItems::FISHING_ROD(),

            self::PLAYER_BREAK => VanillaItems::DIAMOND_PICKAXE(),
            self::PLAYER_BUILD => VanillaBlocks::STONE()->asItem(),
            self::PLAYER_PICKUP => VanillaBlocks::HOPPER()->asItem(),
            self::PLAYER_SWIMMING => VanillaBlocks::WATER_CAULDRON()->asItem(),
            self::PLAYER_DROP => VanillaItems::DIAMOND(),
            self::PLAYER_USE_ITEMS => VanillaItems::STICK(),
            self::PLAYER_EMOTE => VanillaItems::TOTEM(),
            self::PLAYER_CONTAINERS => VanillaBlocks::BARREL()->asItem(),
            self::PLAYER_INTERACT => VanillaItems::SHEARS(),

            self::WORLD_DAMAGE => VanillaItems::ARROW(),
            self::WORLD_ATTACK_PLAYERS => VanillaItems::DIAMOND_SWORD(),
            self::WORLD_ATTACK_MOBS => VanillaItems::IRON_SWORD(),
            self::WORLD_INTERACT_PLAYERS => VanillaBlocks::TRIPWIRE()->asItem(),
            self::WORLD_INTERACT_MOBS => VanillaItems::NAME_TAG(),
            self::WORLD_REGENERATION => VanillaItems::GOLDEN_APPLE(),
            self::WORLD_HUNGER_LOSS => VanillaItems::ROTTEN_FLESH(),

            self::BLOCK => VanillaBlocks::REDSTONE_REPEATER()->asItem(),
        };
    }
}