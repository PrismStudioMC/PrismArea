<?php

namespace PrismArea\session;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

class SessionManager
{
    use SingletonTrait;

    /** @var array<int, Session> */
    private array $sessions = [];

    /**
     * Retrieves an existing session for the player or creates a new one if it doesn't exist.
     *
     * @param Player $player The player for whom to get or create a session.
     * @return Session The session associated with the player.
     */
    public function getOrCreate(Player $player): Session
    {
        $k = $player->getUniqueId()->toString();
        if (isset($this->sessions[$k])) {
            return $this->sessions[$k];
        }

        return $this->sessions[$k] = new Session($player);
    }

    /**
     * Closes the session for the specified player.
     *
     * @param Player $player The player whose session should be closed.
     */
    public function close(Player $player): void
    {
        $k = $player->getUniqueId()->toString();
        if (!isset($this->sessions[$k])) {
            throw new \InvalidArgumentException("Session for player {$player->getName()} does not exist.");
        }

        unset($this->sessions[$k]);
    }
}