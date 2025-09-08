<?php

declare(strict_types=1);

namespace PrismArea\libs\muqsit\invmenu\type;

use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use PrismArea\libs\muqsit\invmenu\inventory\InvMenuInventory;
use PrismArea\libs\muqsit\invmenu\InvMenu;
use PrismArea\libs\muqsit\invmenu\type\graphic\BlockInvMenuGraphic;
use PrismArea\libs\muqsit\invmenu\type\graphic\InvMenuGraphic;
use PrismArea\libs\muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;
use PrismArea\libs\muqsit\invmenu\type\util\InvMenuTypeHelper;

final class BlockFixedInvMenuType implements FixedInvMenuType
{
    public function __construct(
        private readonly Block $block,
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
        $origin = $player->getPosition()->addVector(InvMenuTypeHelper::getBehindPositionOffset($player))->floor();
        if (!InvMenuTypeHelper::isValidYCoordinate($origin->y)) {
            return null;
        }

        return new BlockInvMenuGraphic($this->block, $origin, $this->network_translator);
    }

    public function createInventory(): Inventory
    {
        return new InvMenuInventory($this->size);
    }
}
