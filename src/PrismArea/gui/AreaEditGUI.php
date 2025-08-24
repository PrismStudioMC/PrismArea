<?php

namespace PrismArea\gui;

use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use PrismArea\area\Area;
use PrismArea\area\AreaManager;
use PrismArea\libs\muqsit\invmenu\InvMenu;
use PrismArea\libs\muqsit\invmenu\transaction\InvMenuTransaction;
use PrismArea\libs\muqsit\invmenu\transaction\InvMenuTransactionResult;
use PrismArea\libs\muqsit\invmenu\type\InvMenuTypeIds;
use PrismArea\session\Session;
use PrismArea\types\AreaFlag;
use PrismArea\types\AreaSubFlag;
use PrismArea\types\Translatable;

class AreaEditGUI
{
    private const TAG_STATE = 'state';
    public const GROUP_TITLE = "§f§l§a§g§s";
    public const GROUP_OPEN = "§-";
    public const GROUP_CLOSE = "§+";
    private const GROUP_ITEM = '§b§g';

    private Inventory $inventory;

    /**
     * Cache for sub flags to avoid repeated computation.
     *
     * @var array<string, AreaSubFlag[]>
     */
    private static array $subflagCache = [];

    /**
     * AreaEditGUI constructor.
     *
     * @param Session $session
     * @param Area $area
     */
    public function __construct(
        private Session $session,
        private Area    $area
    )
    {
        if (self::$subflagCache === []) {
            // Initialize the subflag cache only once
            $flagBases = [];
            foreach (AreaFlag::cases() as $f) {
                $flagBases[strtolower($f->name)] = true;
            }

            foreach (AreaSubFlag::cases() as $sub) {
                $base = strtolower($sub->name);

                // Find the base flag for the sub-flag
                while (!isset($flagBases[$base])) {
                    $pos = strrpos($base, '_');
                    if ($pos === false) break;
                    $base = substr($base, 0, $pos);
                }
                self::$subflagCache[$base][] = $sub;
            }
        }
    }

    /**
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->session;
    }

    /**
     * @return Area
     */
    public function getArea(): Area
    {
        return $this->area;
    }

    /**
     * Handles the transaction for the inventory menu.
     *
     * @param InvMenuTransaction $transaction
     * @return InvMenuTransactionResult
     */
    private function handleTransaction(InvMenuTransaction $transaction): InvMenuTransactionResult
    {
        $item = $transaction->getItemClicked();
        $slot = $transaction->getAction()->getSlot();

        $rightClick = false;
        foreach ($transaction->getTransaction()->getActions() as $action) {
            if ($action instanceof DropItemAction) {
                $rightClick = true;
                break;
            }
        }

        $namedTag = $item->getNamedTag();
        $flagData = $namedTag->getTag("flag");
        $subFlagData = $namedTag->getTag("subflag");

        /** @var AreaFlag $flag */
        $flag = null;
        /** @var AreaSubFlag $subFlag */
        $subFlag = null;
        if ($flagData !== null) {
            $flag = AreaFlag::fromString($flagData->getValue());
        } else if ($subFlagData !== null) {
            $subFlag = AreaSubFlag::fromString($subFlagData->getValue());
        }

        if ($flag === null && $subFlag === null) {
            // If the item is not a flag item, we discard the transaction
            return $transaction->discard();
        }

        if (!$rightClick) {
            // If the item is not being right-clicked, we do not toggle the flag
            if ($flag !== null) {
                $this->area->setFlag($flag, !$this->area->hasFlag($flag));
            } else if ($subFlag !== null) {
                $this->area->setSubFlag($subFlag, !$this->area->hasSubFlag($subFlag));
            }
            $this->generateInventory();
            return $transaction->discard();
        }

        if ($namedTag->getTag("state") === null) {
            // If the item does not have a "state" tag, it is not a flag item
            return $transaction->discard();
        }

        $newState = !$namedTag->getByte("state", 0);
        $namedTag->setByte("state", $newState ? 1 : 0);

        $this->inventory->setItem($slot, $item);
        $this->generateInventory();
        return $transaction->discard();
    }

    /**
     * Generates the inventory contents based on the area flags and sub-flags.
     *
     * @return void
     */
    private function generateInventory(): void
    {
        $inv = $this->inventory;
        $lastContents = $inv->getContents();
        $inv->clearAll();

        $lang = $this->session->getLang();
        $index = 0;

        $on = $lang->parse("area.format.on");
        $off = $lang->parse("area.format.off");
        foreach (AreaFlag::cases() as $k => $flag) {
            $item = $flag->getItem();
            $item->getNamedTag()->setString("flag", $flag->name);

            $baseName = strtolower($flag->name);
            $label = $lang->parse("area.edit.gui." . $baseName);
            $status = $this->area->hasFlag($flag);

            $subFlags = self::$subflagCache[$baseName] ?? [];
            if (empty($subFlags)) {
                $inv->setItem($index++, $this->buildItem($item,$label . " " . ($status ? $on : $off), $lang->parse("area.edit.gui.default.lore")));
                continue;
            }

            $prevItem = $lastContents[$index] ?? VanillaItems::AIR();
            $expanded = (bool)$prevItem->getNamedTag()->getByte(self::TAG_STATE, 0);

            $lore = $lang->parse("area.edit.gui.group.lore");
            $groupItem = $this->buildGroupItem($item, $label . " " . ($status ? $on : $off), $lore, $expanded);
            $inv->setItem($index++, $groupItem);

            if ($expanded) {
                foreach ($subFlags as $kk => $sub) {
                    $subItem = $sub->getItem();

                    $baseName = strtolower($sub->name);
                    $label = $lang->parse("area.edit.gui." . $baseName);
                    $status = $this->area->hasSubFlag($sub);
                    $subItem->getNamedTag()->setString("subflag", $sub->name);

                    $inv->setItem($index++,
                        $this->buildItem(
                            $subItem,
                            self::GROUP_ITEM . $label . " " . ($status ? $on : $off),
                            $lang->parse("area.edit.gui.default.lore")
                        )
                    );
                }
            }
        }

        // Fill the remaining slots with empty items
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem()->setCustomName("§r");
        while ($index < $inv->getSize()) {
            $inv->setItem($index++, $glass);
        }

        $list = VanillaItems::BOOK()
            ->setCustomName($lang->parse(Translatable::AREA_EDIT_GUI_LIST, implode(",\n- ", array_map(fn(Area $area) => $area->getName(), AreaManager::getInstance()->getAreas()))));

        $inv->setItem(45, $list);
    }

    /**
     * Builds an item with a custom name and lore.
     *
     * @param Item $item
     * @param string $label
     * @param string $lore
     * @return Item
     */
    private function buildItem(Item $item, string $label, string $lore): Item
    {
        $item->setCustomName($label);
        $item->setLore([$lore]);
        return $item;
    }

    /**
     * Creates an item with a custom name.
     *
     * @param Item $item
     * @param string $label
     * @param string $lore
     * @param bool $expanded
     * @return Item
     */
    private function buildGroupItem(Item $item, string $label, string $lore, bool $expanded): Item
    {
        $tag = $item->getNamedTag();
        $tag->setByte(self::TAG_STATE, $expanded ? 1 : 0);

        $prefix = $expanded ? self::GROUP_OPEN : self::GROUP_CLOSE;
        $item->setCustomName($prefix . $label);
        $item->setLore([$lore]);
        return $item;
    }

    /**
     * Builds the inventory menu for editing the area.
     *
     * @return InvMenu
     */
    public function build(): InvMenu
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName(self::GROUP_TITLE . $this->session->getLang()->parse(Translatable::AREA_EDIT_GUI_TITLE, $this->area->getName()));
        $menu->setListener($this->handleTransaction(...));

        $this->inventory = $menu->getInventory();
        $this->generateInventory();
        return $menu;
    }
}