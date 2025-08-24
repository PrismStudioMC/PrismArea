<?php

declare(strict_types=1);

namespace PrismArea\libs\muqsit\invmenu\session;

use pocketmine\player\Player;
use PrismArea\libs\muqsit\invmenu\session\network\PlayerNetwork;

final class PlayerSession{

	public ?PlayerWindowDispatcher $dispatcher = null;
	public ?InvMenuInfo $current = null;

	public function __construct(
		readonly public Player $player,
		readonly public PlayerNetwork $network
	){}

	/**
	 * @internal
	 */
	public function finalize() : void{
		$this->network->finalize();
		$this->dispatcher?->finalize(); // dispatcher finalized first, it has authority to nullify current
		$this->dispatcher = null;
		if($this->current !== null){
			$this->current->graphic->remove($this->player);
			$this->player->removeCurrentWindow();
			$this->current = null;
		}
	}
}
