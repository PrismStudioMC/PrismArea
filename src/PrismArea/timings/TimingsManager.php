<?php

namespace PrismArea\timings;

use pocketmine\timings\TimingsHandler;
use pocketmine\utils\SingletonTrait;
use PrismArea\Loader;

class TimingsManager
{
    use SingletonTrait;

    private Loader $loader;
    private bool $initialized = false;

    private TimingsHandler $plugin;
    private TimingsHandler $searchAreas;
    private TimingsHandler $calculatingAbilities;
    private TimingsHandler $inventoryUpdate;
    private TimingsHandler $blocksProcessing;

    public function __construct()
    {
        self::setInstance($this);
    }

    /**
     * @param Loader $loader
     * @return void
     */
    public function load(Loader $loader): void
    {
        $this->loader = $loader;
        $this->init();
    }

    /**
     * @return void
     */
    private function init(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        $this->plugin = new TimingsHandler("PrismArea");

        $this->searchAreas = new TimingsHandler("Searching Areas", $this->plugin);
        $this->calculatingAbilities = new TimingsHandler("Calculating Abilities", $this->plugin);
        $this->inventoryUpdate = new TimingsHandler("Inventory Update", $this->plugin);
        $this->blocksProcessing = new TimingsHandler("Blocks Processing", $this->plugin);
    }

    /**
     * @return TimingsHandler
     */
    public function getPlugin(): TimingsHandler
    {
        $this->init();
        return $this->plugin;
    }

    /**
     * @return TimingsHandler
     */
    public function getSearchAreas(): TimingsHandler
    {
        $this->init();
        return $this->searchAreas;
    }

    /**
     * @return TimingsHandler
     */
    public function getCalculatingAbilities(): TimingsHandler
    {
        $this->init();
        return $this->calculatingAbilities;
    }

    /**
     * @return TimingsHandler
     */
    public function getInventoryUpdate(): TimingsHandler
    {
        $this->init();
        return $this->inventoryUpdate;
    }

    /**
     * @return TimingsHandler
     */
    public function getBlocksProcessing(): TimingsHandler
    {
        $this->init();
        return $this->blocksProcessing;
    }
}
