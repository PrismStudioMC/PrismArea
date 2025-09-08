<?php

namespace PrismArea\types;

enum Translatable: string
{
    case AREA_COMMAND_DESCRIPTION = "area.command.description";
    case AREA_COMMAND_SELECTION_INVALID = "area.command.selection.invalid";
    case AREA_COMMAND_INVALID_NAME = "area.command.invalid_name";
    case AREA_COMMAND_UNKNOWN_AREA = "area.command.unknown_area";

    case AREA_COMMAND_LIST_EMPTY = "area.command.list.empty";
    case AREA_COMMAND_LIST_SUCCESS = "area.command.list.success";

    case AREA_COMMAND_CREATE_EXISTS = "area.command.create.exists";
    case AREA_COMMAND_CREATE_SUCCESS = "area.command.create.success";
    case AREA_COMMAND_DELETE_SUCCESS = "area.command.delete.success";
    case AREA_COMMAND_COPY_SUCCESS = "area.command.copy.success";
    case AREA_COMMAND_PRIORITY_SUCCESS = "area.command.priority.success";

    case AREA_COMMAND_SELECT_SUCCESS = "area.command.select.success";
    case AREA_COMMAND_UNSELECT_SUCCESS = "area.command.unselect.success";

    case AREA_INTERACT_DENIED = "area.interact.denied";

    case PLAYER_BREAK_DENIED = "player.break.denied";
    case PLAYER_PLACE_DENIED = "player.place.denied";
    case PLAYER_CONTAINERS_DENIED = "player.containers.denied";
    case PLAYER_USE_ITEMS_DENIED = "player.use.items.denied";
    case PLAYER_DROP_DENIED = "player.drop.denied";

    case WORLD_ATTACK_PLAYERS_DENIED = "world.attack.players.denied";
    case WORLD_ATTACK_MOBS_DENIED = "world.attack.mobs.denied";
    case WORLD_INTERACT_PLAYERS_DENIED = "world.interact.players.denied";
    case WORLD_INTERACT_MOBS_DENIED = "world.interact.mobs.denied";

    case AREA_EDIT_GUI_TITLE = "area.edit.gui.title";
    case AREA_EDIT_GUI_LIST = "area.edit.gui.list";

    case AREA_PRIORITY_GUI_TITLE = "area.priority.gui.title";

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->value;
    }
}
