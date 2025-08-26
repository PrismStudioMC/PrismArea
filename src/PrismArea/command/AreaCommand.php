<?php

namespace PrismArea\command;

use platz1de\EasyEdit\command\exception\NoSelectionException;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\session\SessionManager as SManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\AxisAlignedBB;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandEnumConstraint;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use PrismAPI\utils\PrismCommand;
use PrismArea\area\Area;
use PrismArea\area\AreaManager;
use PrismArea\gui\AreaEditGUI;
use PrismArea\session\SessionManager;
use PrismArea\types\Translatable;

class AreaCommand extends PrismCommand
{
    public function __construct()
    {
        parent::__construct(
            "area",
            "Manage area",
            "/area <create|delete|list|info|setflag|removeflag>",
        );
        $this->setPermission("prism.area.*");
    }

    /**
     * Builds the command overloads for the area command.
     * @param CommandEnum[]           $hardcodedEnums
     * @param CommandEnum[]           $softEnums
     * @param CommandEnumConstraint[] $enumConstraints
     * @return CommandOverload[]
     */
    public function buildOverloads(array &$hardcodedEnums, array &$softEnums, array &$enumConstraints) : array
    {
        return [
            new CommandOverload(chaining: false, parameters: [
                CommandParameter::enum("sub", new CommandEnum("Enum#" . mt_rand(9999, 99999999), ["list"], false), 0, false),
            ]),
            new CommandOverload(chaining: false, parameters: [
                CommandParameter::enum("sub", new CommandEnum("Enum#" . mt_rand(9999, 99999999), ["create"], false), 0, false),
                CommandParameter::standard("name", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, false),
            ]),
            new CommandOverload(chaining: false, parameters: [
                CommandParameter::enum("sub", new CommandEnum("Enum#" . mt_rand(9999, 99999999), ["prioritize"], false), 0, false),
                CommandParameter::standard("target", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, false),
                CommandParameter::standard("reference", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, false),
            ]),
            new CommandOverload(chaining: false, parameters: [
                CommandParameter::enum("sub", new CommandEnum("Enum#" . mt_rand(9999, 99999999), ["edit"], false), 0, false),
                CommandParameter::standard("name", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, false),
            ]),
            new CommandOverload(chaining: false, parameters: [
                CommandParameter::enum("sub", new CommandEnum("Enum#" . mt_rand(9999, 99999999), ["copy"], false), 0, false),
                CommandParameter::standard("area1", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, false),
                CommandParameter::standard("area2", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, false),
            ]),
            new CommandOverload(chaining: false, parameters: [
                CommandParameter::enum("sub", new CommandEnum("Enum#" . mt_rand(9999, 99999999), ["visualize"], false), 0, false),
                CommandParameter::standard("name", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, false),
            ]),
            new CommandOverload(chaining: false, parameters: [
                CommandParameter::enum("sub", new CommandEnum("Enum#" . mt_rand(9999, 99999999), ["select"], false), 0, false),
                CommandParameter::standard("name", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, false),
            ]),
            new CommandOverload(chaining: false, parameters: [
                CommandParameter::enum("sub", new CommandEnum("Enum#" . mt_rand(9999, 99999999), ["unselect"], false), 0, false),
                CommandParameter::standard("name", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, false),
            ]),
        ];
    }

    /**
     * Executes the area command.
     *
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "You must be a player to use this command.");
            return;
        }

        $session = SessionManager::getInstance()->getOrCreate($sender);

        $size = count($args);
        if($size < 1) {
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return;
        }

        $subCommand = strtolower(array_shift($args));
        switch($subCommand) {
            case "list":
            {
                if ($size !== 1) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /area list");
                    return;
                }

                $areas = AreaManager::getInstance()->getAreas();
                if (empty($areas)) {
                    $session->sendMessage(Translatable::AREA_COMMAND_LIST_EMPTY);
                    return;
                }

                $areaList = implode(", ", array_map(fn(Area $area) => $area->getName(), $areas));
                $session->sendMessage(Translatable::AREA_COMMAND_LIST_SUCCESS, $areaList);
                break;
            }
            case "create":
            {
                if ($size !== 2) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /area create <name>");
                    return;
                }

                $name = array_shift($args);
                if (strlen($name) < 2 || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                    $session->sendMessage(Translatable::AREA_COMMAND_INVALID_NAME);
                    return;
                }

                try {
                    $selection = SManager::get($sender)->getSelection(); // EasyEdit selection
                } catch (NoSelectionException $e) {
                    $session->sendMessage(Translatable::AREA_COMMAND_SELECTION_INVALID);
                    return;
                }

                $area = AreaManager::getInstance()->getArea($name);
                if ($area !== null) {
                    $session->sendMessage(Translatable::AREA_COMMAND_CREATE_EXISTS);
                    return;
                }

                $aabb = new AxisAlignedBB(
                    min($selection->getPos1()->x, $selection->getPos2()->x),
                    min($selection->getPos1()->y, $selection->getPos2()->y),
                    min($selection->getPos1()->z, $selection->getPos2()->z),
                    max($selection->getPos1()->x, $selection->getPos2()->x),
                    max($selection->getPos1()->y, $selection->getPos2()->y),
                    max($selection->getPos1()->z, $selection->getPos2()->z)
                );

                $area = new Area(
                    count(AreaManager::getInstance()->getAreas()), // Unique ID based on current area count
                    $name,
                    $selection->getWorld(),
                    $aabb,
                );
                AreaManager::getInstance()->register($area);
                $session->sendMessage(Translatable::AREA_COMMAND_CREATE_SUCCESS, $name);
                break;
            }
            case "delete": {
                if ($size !== 2) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /area delete <name>");
                    return;
                }

                $name = array_shift($args);
                if (strlen($name) < 2 || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                    $session->sendMessage(Translatable::AREA_COMMAND_INVALID_NAME);
                    return;
                }

                $area = AreaManager::getInstance()->getArea($name);
                if ($area === null) {
                    $session->sendMessage(Translatable::AREA_COMMAND_UNKNOWN_AREA, $name);
                    return;
                }

                AreaManager::getInstance()->delete($area);
                $session->sendMessage(Translatable::AREA_COMMAND_DELETE_SUCCESS, $name);
                break;
            }
            case "prioritize": {
                if ($size !== 3) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /area prioritize <target> <reference>");
                    return;
                }

                $targetName = array_shift($args);
                if (strlen($targetName) < 2 || !preg_match('/^[a-zA-Z0-9_]+$/', $targetName)) {
                    $session->sendMessage(Translatable::AREA_COMMAND_INVALID_NAME);
                    return;
                }

                $referenceName = array_shift($args);
                if (strlen($referenceName) < 2 || !preg_match('/^[a-zA-Z0-9_]+$/', $referenceName)) {
                    $session->sendMessage(Translatable::AREA_COMMAND_INVALID_NAME);
                    return;
                }

                $targetArea = AreaManager::getInstance()->getArea($targetName);
                if ($targetArea === null) {
                    $session->sendMessage(Translatable::AREA_COMMAND_UNKNOWN_AREA, $targetName);
                    return;
                }

                $referenceArea = AreaManager::getInstance()->getArea($referenceName);
                if ($referenceArea === null) {
                    $session->sendMessage(Translatable::AREA_COMMAND_UNKNOWN_AREA, $referenceName);
                    return;
                }

                AreaManager::getInstance()->prioritize($targetArea, $referenceArea);
                $session->sendMessage(Translatable::AREA_COMMAND_PRIORITY_SUCCESS, $targetName, $referenceName);
                break;
            }
            case "edit": {
                if ($size !== 2) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /area edit <name>");
                    return;
                }

                $name = array_shift($args);
                if (strlen($name) < 2 || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                    $session->sendMessage(Translatable::AREA_COMMAND_INVALID_NAME);
                    return;
                }

                $area = AreaManager::getInstance()->getArea($name);
                if ($area === null) {
                    $session->sendMessage(Translatable::AREA_COMMAND_UNKNOWN_AREA, $name);
                    return;
                }

                $gui = (new AreaEditGUI($session, $area))->build();
                $gui->send($sender);
                break;
            }
            case "copy": {
                if ($size !== 3) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /area copy <area1> <area2>");
                    return;
                }

                $name1 = array_shift($args);
                $name2 = array_shift($args);

                if (strlen($name1) < 2 || !preg_match('/^[a-zA-Z0-9_]+$/', $name1) ||
                    strlen($name2) < 2 || !preg_match('/^[a-zA-Z0-9_]+$/', $name2)) {
                    $session->sendMessage(Translatable::AREA_COMMAND_INVALID_NAME);
                    return;
                }

                $area1 = AreaManager::getInstance()->getArea($name1);
                $area2 = AreaManager::getInstance()->getArea($name2);
                if ($area1 === null || $area2 === null) {
                    $session->sendMessage(Translatable::AREA_COMMAND_UNKNOWN_AREA, $area1 === null ? $name1 : $name2);
                    return;
                }

                $area2->setFlags($area1->getFlags());
                $area2->setSubFlags($area1->getSubFlags());
                $session->sendMessage(Translatable::AREA_COMMAND_COPY_SUCCESS, $name1, $name2);
                break;
            }
            case "visualize": {
                if ($size !== 2) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /area edit <name>");
                    return;
                }

                $name = array_shift($args);
                if (strlen($name) < 2 || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                    $session->sendMessage(Translatable::AREA_COMMAND_INVALID_NAME);
                    return;
                }

                $area = AreaManager::getInstance()->getArea($name);
                if ($area === null) {
                    $session->sendMessage(Translatable::AREA_COMMAND_UNKNOWN_AREA, $name);
                    return;
                }

                $session->visualize($area);
                break;
            }
            case "select": {
                if ($size !== 2) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /area select <name>");
                    return;
                }

                $name = array_shift($args);
                if (strlen($name) < 2 || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                    $session->sendMessage(Translatable::AREA_COMMAND_INVALID_NAME);
                    return;
                }

                $area = AreaManager::getInstance()->getArea($name);
                if ($area === null) {
                    $session->sendMessage(Translatable::AREA_COMMAND_UNKNOWN_AREA, $name);
                    return;
                }

                $aabb = $area->getAABB();

                $s = SManager::get($sender);
                $reflectionClass = new \ReflectionClass(Session::class);

                try {
                    $reflectionClass->getMethod("createSelectionInWorld")->invoke($s, $area->getWorld()->getFolderName());
                } catch (\ReflectionException $e) {
                    throw new \RuntimeException("Failed to create selection in world: " . $e->getMessage());
                }

                $selection = $reflectionClass->getProperty('selection')->getValue($s);
                $selection->setPos(new BlockVector($aabb->minX, $aabb->minY, $aabb->minZ), 1);
                $selection->setPos(new BlockVector($aabb->maxX, $aabb->maxY, $aabb->maxZ), 2);

                $s->updateSelectionHighlight();
                $session->sendMessage(Translatable::AREA_COMMAND_SELECT_SUCCESS, $name);
                break;
            }
            case "unselect": {
                if ($size !== 2) {
                    $sender->sendMessage(TextFormat::RED . "Usage: /area unselect <name>");
                    return;
                }

                $name = array_shift($args);
                if (strlen($name) < 2 || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                    $session->sendMessage(Translatable::AREA_COMMAND_INVALID_NAME);
                    return;
                }

                $area = AreaManager::getInstance()->getArea($name);
                if ($area === null) {
                    $session->sendMessage(Translatable::AREA_COMMAND_UNKNOWN_AREA, $name);
                    return;
                }

                try {
                    $selection = SManager::get($sender)->getSelection(); // EasyEdit selection
                } catch (NoSelectionException $e) {
                    $session->sendMessage(Translatable::AREA_COMMAND_SELECTION_INVALID);
                    return;
                }

                $aabb = $area->getAABB();
                $pos1 = new BlockVector($aabb->minX, $aabb->minY, $aabb->minZ);
                $pos2 = new BlockVector($aabb->maxX, $aabb->maxY, $aabb->maxZ);

                if($selection->getPos1() !== $pos1 || $selection->getPos2() !== $pos2) {
                    $session->sendMessage(Translatable::AREA_COMMAND_SELECTION_INVALID);
                    return;
                }

                $s = SManager::get($sender);
                $reflectionClass = new \ReflectionClass(Session::class);
                $reflectionClass->getProperty('selection')->setValue($s, null);
                $s->updateSelectionHighlight();

                $session->sendMessage(Translatable::AREA_COMMAND_UNSELECT_SUCCESS, $name);
                break;
            }
            default:
                $sender->sendMessage(TextFormat::RED . "Unknown subcommand: " . $subCommand);
        }
    }
}