<?php

namespace PrismArea;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use PrismAPI\Loader as PrismAPI;
use PrismAPI\utils\ResourcePack;
use PrismArea\area\AreaManager;
use PrismArea\command\AreaCommand;
use PrismArea\lang\LangManager;
use PrismArea\libs\muqsit\invmenu\InvMenuHandler;
use PrismArea\listener\AbilitiesListener;
use PrismArea\listener\BlockListener;
use PrismArea\listener\CommandListener;
use PrismArea\listener\PlayerListener;
use PrismArea\listener\WorldListener;
use Symfony\Component\Filesystem\Path;

class Loader extends PluginBase
{
    use SingletonTrait;

    protected function onLoad(): void
    {
        self::setInstance($this);
        $this->saveDefaultConfig();

        @mkdir(Path::join($this->getDataFolder(), "lang"), 0777, true);
        $this->saveResource("lang/en_US.ini");
        $this->saveResource("pack.zip");
    }

    /**
     * Initializes the plugin and loads area data.
     *
     * This method is called when the plugin is enabled.
     * It loads area data from the specified JSON file.
     */
    public function onEnable(): void
    {
        $config = $this->getConfig();

        // Check if PrismAPI plugin is installed
        if (!class_exists(PrismAPI::class)) {
            $this->getLogger()->error("PrismAPI plugin not found. Disabling PrismArea.");
            $this->getLogger()->error("You can download this API at https://github.com/PrismStudioMC/PrismAPI");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        // Check if InvMenu plugin is installed
        if (!class_exists(InvMenuHandler::class)) {
            $this->getLogger()->error("InvMenu plugin not found. Please install it to use the area menu features.");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        // Check if InvMenuHandler is already registered
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        $areaManager = AreaManager::getInstance();
        $areaManager->load(Path::join($this->getDataFolder(), "areas.json"));

        $langManager = LangManager::getInstance();
        $langManager->load(Path::join($this->getDataFolder(), "lang"));

        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener($this, $areaManager), $this);
        $this->getServer()->getPluginManager()->registerEvents(new WorldListener($this, $areaManager), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockListener($this, $areaManager), $this);

        if ($config->get("use-abilities", true)) {
            // Register the AbilitiesListener if the config option is enabled
            new AbilitiesListener($this);
        }

        $this->getServer()->getCommandMap()->register("area", new AreaCommand());
        ResourcePack::load(Path::join($this->getDataFolder(), "pack.zip")); // Load the resource pack
    }

    /**
     * Cleans up resources when the plugin is disabled.
     *
     * This method is called when the plugin is disabled.
     * It closes the area manager to release any resources.
     */
    public function onDisable(): void
    {
        AreaManager::getInstance()->close();
    }
}