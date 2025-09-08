<?php

declare(strict_types=1);

namespace PrismArea\libs\muqsit\invmenu\type\graphic\network;

use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use PrismArea\libs\muqsit\invmenu\session\InvMenuInfo;
use PrismArea\libs\muqsit\invmenu\session\PlayerSession;

final class MultiInvMenuGraphicNetworkTranslator implements InvMenuGraphicNetworkTranslator
{
    /**
     * @param InvMenuGraphicNetworkTranslator[] $translators
     */
    public function __construct(
        private readonly array $translators
    ) {
    }

    public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet): void
    {
        foreach ($this->translators as $translator) {
            $translator->translate($session, $current, $packet);
        }
    }
}
