<?php

namespace PrismArea\extensions;

use DaPigGuy\PiggyFactions\claims\ClaimsManager;
use DaPigGuy\PiggyFactions\permissions\FactionPermission;
use DaPigGuy\PiggyFactions\players\PlayerManager;
use pocketmine\player\Player;
use PrismArea\events\PlayerRecalculateAbilitiesEvent;
use PrismArea\types\AbilitiesLayer;

class PiggyFactionsExtension extends Extension
{
    /**
     * PiggyFactionsExtension constructor.
     */
    public function __construct()
    {
        parent::__construct("PiggyFactions");
    }

    /**
     * Handles the PlayerRecalculateAbilitiesEvent.
     * @param PlayerRecalculateAbilitiesEvent $ev
     * @return void
     */
    public function handle(PlayerRecalculateAbilitiesEvent $ev): void
    {
        $player = $ev->getPlayer();
        $abilities = $ev->getNewAbilities();

        // Check if the player is in a claimed area and adjust abilities accordingly
        if (!$this->canAffectArea($player)) {
            $abilities[AbilitiesLayer::ABILITY_BUILD] = false; // disabling block placing
            $abilities[AbilitiesLayer::ABILITY_MINE] = false; // disabling block breaking
        }

        // Check for container interaction permission
        if (!$this->canAffectArea($player, FactionPermission::CONTAINERS)) {
            $abilities[AbilitiesLayer::ABILITY_OPEN_CONTAINERS] = false; // disabling opening containers
        }

        // Check for interaction permission
        if (!$this->canAffectArea($player, FactionPermission::INTERACT)) {
            $abilities[AbilitiesLayer::RIGHT_CLICK] = false; // disabling all right click interaction
        }

        $ev->setNewAbilities($abilities);
    }

    /**
     * Checks if a player can affect an area based on their faction permissions.
     *
     * @param Player $player
     * @param string $type
     * @return bool
     */
    public function canAffectArea(Player $player, string $type = FactionPermission::BUILD): bool
    {
        $member = PlayerManager::getInstance()->getPlayer($player);
        $claim = ClaimsManager::getInstance()->getClaimByPosition($player->getPosition());
        if ($claim !== null) {
            return $member !== null && $claim->getFaction()->hasPermission($member, $type);
        }
        return true;
    }
}
