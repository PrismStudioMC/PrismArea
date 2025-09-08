<?php

declare(strict_types=1);

namespace PrismArea\libs\muqsit\invmenu\type;

use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use PrismArea\libs\muqsit\invmenu\InvMenu;
use PrismArea\libs\muqsit\invmenu\type\graphic\InvMenuGraphic;

interface InvMenuType
{
    public function createGraphic(InvMenu $menu, Player $player): ?InvMenuGraphic;

    public function createInventory(): Inventory;
}
