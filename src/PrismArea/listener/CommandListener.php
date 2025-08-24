<?php

namespace PrismArea\listener;

use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandData;
use PrismArea\command\AreaCommand;
use PrismArea\Loader;
use PrismArea\session\SessionManager;
use PrismArea\types\Translatable;

class CommandListener
{
    public function __construct(
        private readonly Loader $loader,
    )
    {
        $this->loader->getServer()->getPluginManager()->registerEvent(
            DataPacketSendEvent::class,
            $this->handlePacketSend(...),
            EventPriority::LOWEST,
            $this->loader
        );
    }

    /**
     * Handles the DataPacketSendEvent to manage command completions.
     *
     * @param DataPacketSendEvent $ev
     */
    public function handlePacketSend(DataPacketSendEvent $ev): void
    {
        $packets = $ev->getPackets();
        $origin = $ev->getTargets();

        // Ensure that we only handle the event if there is exactly one packet and one origin
        if(count($packets) !== 1 || count($origin) !== 1) {
            return;
        }

        $packet = array_shift($packets);
        $origin = array_shift($origin);

        if(!$packet instanceof AvailableCommandsPacket) {
            return;
        }

        $player = $origin->getPlayer();
        if($player === null) {
            return; // Player is not online, do not modify the packet
        }

        $session = SessionManager::getInstance()->getOrCreate($player);

        /** @var AreaCommand $command */
        $command = $this->loader->getServer()->getCommandMap()->getCommand("area");
        if($command === null) {
            return; // Area command does not exist
        }

        $k = $command->getLabel();
        $currentData = $packet->commandData[$k] ?? null;
        if($currentData === null) {
            return; // No command data for the area command
        }

        $packet->commandData[$command->getLabel()] = new CommandData(
            $command->getName(),
            $session->getLang()->parse(Translatable::AREA_COMMAND_DESCRIPTION),
            $currentData->getFlags(),
            $currentData->getPermission(),
            $currentData->getAliases(),
            $command->buildOverloads($packet->hardcodedEnums, $packet->softEnums, $packet->enumConstraints),
            $currentData->getChainedSubCommandData()
        );
    }
}