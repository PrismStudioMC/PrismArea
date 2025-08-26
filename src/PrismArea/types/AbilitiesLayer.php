<?php

namespace PrismArea\types;

final class AbilitiesLayer
{
    public const LAYER_CACHE = 0;
    public const LAYER_BASE = 1; // default abilities
    public const LAYER_SPECTATOR = 2;
    public const LAYER_COMMANDS = 3;
    public const LAYER_EDITOR = 4;
    public const LAYER_LOADING_SCREEN = 5;

    public const ABILITY_BUILD = 0;
    public const ABILITY_MINE = 1;
    public const ABILITY_DOORS_AND_SWITCHES = 2; //disabling this also disables dropping items (???)
    public const ABILITY_OPEN_CONTAINERS = 3;
    public const ABILITY_ATTACK_PLAYERS = 4;
    public const ABILITY_ATTACK_MOBS = 5;
    public const ABILITY_OPERATOR = 6;

    public const ABILITY_DROP = 12; //???
}