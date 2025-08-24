<?php

namespace PrismArea\types;

use PrismArea\trait\AreaFlagItemTrait;

enum AreaFlag
{
    use AreaFlagItemTrait;

    case LEFT_CLICK;
    case RIGHT_CLICK;

    case PLAYER_BREAK;
    case PLAYER_BUILD;
    case PLAYER_USE_ITEMS;
    case PLAYER_PICKUP;
    case PLAYER_DROP;
    case PLAYER_SWIMMING;
    case PLAYER_EMOTE;
    case PLAYER_CONTAINERS;
    case PLAYER_INTERACT;

    case WORLD_DAMAGE;

    case WORLD_ATTACK_PLAYERS;
    case WORLD_ATTACK_MOBS;
    case WORLD_INTERACT_PLAYERS;
    case WORLD_INTERACT_MOBS;
    case WORLD_REGENERATION;
    case WORLD_HUNGER_LOSS;

    case BLOCK;

    /**
     * Checks if the current AreaFlag is equal to another AreaFlag instance.
     *
     * @param AreaFlag $flag The AreaFlag instance to compare with.
     * @return bool True if both flags are the same, false otherwise.
     */
    public function equal(AreaFlag $flag): bool
    {
        return $this === $flag;
    }

    /**
     * Converts a string representation of an area flag to the corresponding AreaFlag enum value.
     *
     * @param string $flag The string representation of the flag.
     * @return AreaFlag|null The corresponding AreaFlag enum value, or null if the flag is not recognized.
     */
    public static function fromString(string $flag): ?AreaFlag
    {
        return match (strtolower($flag)) {
            'left_click' => self::LEFT_CLICK,
            'right_click' => self::RIGHT_CLICK,
            'player_break' => self::PLAYER_BREAK,
            'player_build' => self::PLAYER_BUILD,
            'player_pickup' => self::PLAYER_PICKUP,
            'player_drop' => self::PLAYER_DROP,
            'player_swimming' => self::PLAYER_SWIMMING,
            'player_use_items' => self::PLAYER_USE_ITEMS,
            'player_emote' => self::PLAYER_EMOTE,
            'player_interact' => self::PLAYER_INTERACT,
            'player_containers' => self::PLAYER_CONTAINERS,
            'world_damage' => self::WORLD_DAMAGE,
            'world_attack_players' => self::WORLD_ATTACK_PLAYERS,
            'world_attack_mobs' => self::WORLD_ATTACK_MOBS,
            'world_interact_players' => self::WORLD_INTERACT_PLAYERS,
            'world_interact_mobs' => self::WORLD_INTERACT_MOBS,
            'world_regeneration' => self::WORLD_REGENERATION,
            'world_hunger_loss' => self::WORLD_HUNGER_LOSS,
            'block' => self::BLOCK,
            default => null
        };
    }
}