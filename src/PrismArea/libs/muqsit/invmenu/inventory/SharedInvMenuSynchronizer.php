<?php

declare(strict_types=1);

namespace PrismArea\libs\muqsit\invmenu\inventory;

use pocketmine\inventory\Inventory;
use PrismArea\libs\muqsit\invmenu\InvMenu;

final class SharedInvMenuSynchronizer
{
    private readonly Inventory $inventory;
    private readonly SharedInventorySynchronizer $synchronizer;
    private readonly SharedInventoryNotifier $notifier;

    public function __construct(InvMenu $menu, Inventory $inventory)
    {
        $this->inventory = $inventory;

        $menu_inventory = $menu->getInventory();
        $this->synchronizer = new SharedInventorySynchronizer($menu_inventory);
        $inventory->getListeners()->add($this->synchronizer);

        $this->notifier = new SharedInventoryNotifier($this->inventory, $this->synchronizer);
        $menu_inventory->setContents($inventory->getContents());
        $menu_inventory->getListeners()->add($this->notifier);
    }

    public function destroy(): void
    {
        $this->synchronizer->getSynchronizingInventory()->getListeners()->remove($this->notifier);
        $this->inventory->getListeners()->remove($this->synchronizer);
    }
}
