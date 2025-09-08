<?php

namespace PrismArea\events;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerRecalculateAbilitiesEvent extends PlayerEvent
{
    public function __construct(
        protected Player $player,
        protected array  $prevAbilities,
        protected array  $newAbilities,
    ) {
    }

    /**
     * @return array
     */
    public function getPrevAbilities(): array
    {
        return $this->prevAbilities;
    }

    /**
     * @return array
     */
    public function getNewAbilities(): array
    {
        return $this->newAbilities;
    }

    /**
     * @param array $newAbilities
     */
    public function setNewAbilities(array $newAbilities): void
    {
        $this->newAbilities = $newAbilities;
    }
}
