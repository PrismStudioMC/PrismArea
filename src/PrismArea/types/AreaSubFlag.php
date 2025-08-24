<?php

namespace PrismArea\types;

use PrismArea\trait\AreaSubFlagItemTrait;

enum AreaSubFlag
{
    use AreaSubFlagItemTrait;

    case PLAYER_USE_ITEMS_ENDER_PEARL;
    case PLAYER_USE_ITEMS_SNOWBALL;
    case PLAYER_USE_ITEMS_EGG;
    case PLAYER_USE_ITEMS_POTIONS;
    case PLAYER_USE_ITEMS_SPLASH_POTIONS;

    case PLAYER_CONTAINERS_CHEST;
    case PLAYER_CONTAINERS_ENDER_CHEST;
    case PLAYER_CONTAINERS_FURNACE;
    case PLAYER_CONTAINERS_BARREL;
    case PLAYER_CONTAINERS_HOPPER;
    case PLAYER_CONTAINERS_BREWING_STAND;
    case PLAYER_CONTAINERS_SHULKER_BOX;

    case PLAYER_INTERACT_AXE;
    case PLAYER_INTERACT_SHOVEL;
    case PLAYER_INTERACT_HOE;
    case PLAYER_INTERACT_BUCKET;
    case PLAYER_INTERACT_FLINT_AND_STEEL;

    case WORLD_DAMAGE_FALL;

    case BLOCK_FORM;
    case BLOCK_EXPLOSION;
    case BLOCK_BURN;
    case BLOCK_SPREAD;
    case BLOCK_LEAVES_DECAY;
    case BLOCK_GROW;
    case BLOCK_MELT;
    case BLOCK_SIGN_CHANGE;

    public static function fromString(string $flag): ?AreaSubFlag
    {
        return match (strtolower($flag)) {
            'player_use_items_ender_pearl' => self::PLAYER_USE_ITEMS_ENDER_PEARL,
            'player_use_items_snowball' => self::PLAYER_USE_ITEMS_SNOWBALL,
            'player_use_items_egg' => self::PLAYER_USE_ITEMS_EGG,
            'player_use_items_potions' => self::PLAYER_USE_ITEMS_POTIONS,
            'player_use_items_splash_potions' => self::PLAYER_USE_ITEMS_SPLASH_POTIONS,

            'player_containers_chest' => self::PLAYER_CONTAINERS_CHEST,
            'player_containers_ender_chest' => self::PLAYER_CONTAINERS_ENDER_CHEST,
            'player_containers_furnace' => self::PLAYER_CONTAINERS_FURNACE,
            'player_containers_barrel' => self::PLAYER_CONTAINERS_BARREL,
            'player_containers_hopper' => self::PLAYER_CONTAINERS_HOPPER,
            'player_containers_brewing_stand' => self::PLAYER_CONTAINERS_BREWING_STAND,
            'player_containers_shulker_box' => self::PLAYER_CONTAINERS_SHULKER_BOX,

            'player_interact_axe' => self::PLAYER_INTERACT_AXE,
            'player_interact_shovel' => self::PLAYER_INTERACT_SHOVEL,
            'player_interact_hoe' => self::PLAYER_INTERACT_HOE,
            'player_interact_bucket' => self::PLAYER_INTERACT_BUCKET,
            'player_interact_flint_and_steel' => self::PLAYER_INTERACT_FLINT_AND_STEEL,

            'world_damage_fall' => self::WORLD_DAMAGE_FALL,

            'block_form' => self::BLOCK_FORM,
            'block_explosion' => self::BLOCK_EXPLOSION,
            'block_burn' => self::BLOCK_BURN,
            'block_spread' => self::BLOCK_SPREAD,
            'block_leaves_decay' => self::BLOCK_LEAVES_DECAY,
            'block_grow' => self::BLOCK_GROW,
            'block_melt' => self::BLOCK_MELT,
            'block_sign_change' => self::BLOCK_SIGN_CHANGE,
            default => throw new \InvalidArgumentException("Unknown AreaSubFlag: $flag"),
        };
    }
}