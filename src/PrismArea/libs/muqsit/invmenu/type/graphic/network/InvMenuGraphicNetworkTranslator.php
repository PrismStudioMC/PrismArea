<?php

declare(strict_types=1);

namespace PrismArea\libs\muqsit\invmenu\type\graphic\network;

use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use PrismArea\libs\muqsit\invmenu\session\InvMenuInfo;
use PrismArea\libs\muqsit\invmenu\session\PlayerSession;

interface InvMenuGraphicNetworkTranslator
{
    public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet): void;
}
