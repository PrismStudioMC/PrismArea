<?php

declare(strict_types=1);

namespace PrismArea\libs\muqsit\invmenu\type;

use pocketmine\inventory\Inventory;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\player\Player;
use PrismArea\libs\muqsit\invmenu\inventory\InvMenuInventory;
use PrismArea\libs\muqsit\invmenu\InvMenu;
use PrismArea\libs\muqsit\invmenu\type\graphic\ActorInvMenuGraphic;
use PrismArea\libs\muqsit\invmenu\type\graphic\InvMenuGraphic;
use PrismArea\libs\muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;

final class ActorFixedInvMenuType implements FixedInvMenuType
{
    /**
     * @param string $actor_identifier
     * @param int $actor_runtime_identifier
     * @param array<int, MetadataProperty> $actor_metadata
     * @param int $size
     * @param InvMenuGraphicNetworkTranslator|null $network_translator
     */
    public function __construct(
        private readonly string $actor_identifier,
        private readonly int $actor_runtime_identifier,
        private readonly array $actor_metadata,
        private readonly int $size,
        private readonly ?InvMenuGraphicNetworkTranslator $network_translator = null
    ) {
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function createGraphic(InvMenu $menu, Player $player): ?InvMenuGraphic
    {
        return new ActorInvMenuGraphic($this->actor_identifier, $this->actor_runtime_identifier, $this->actor_metadata, $this->network_translator);
    }

    public function createInventory(): Inventory
    {
        return new InvMenuInventory($this->size);
    }
}
