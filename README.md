# Prism Studio - PrismArea

A powerful and flexible area management plugin for PocketMine-MP servers that allows server administrators to create, manage, and control player interactions within defined regions.

## Features

### **Core Functionality**
- **Area Creation & Management**: Create custom areas with specific boundaries and properties
- **Priority System**: Set area priorities to handle overlapping regions
- **World Support**: Works across multiple worlds and dimensions
- **Persistent Storage**: Areas are saved and loaded automatically

### **Comprehensive Protection System**
- **Player Actions**: Control building, breaking, item usage, pickup, dropping, swimming, emotes, and container interactions
- **World Interactions**: Manage damage, mob attacks, player interactions, regeneration, and hunger loss
- **Block Control**: Fine-grained control over block interactions
- **Click Protection**: Protect against left and right click actions

### **Management Tools**
- **Visual Area Editor**: Intuitive GUI for area creation and modification
- **EasyEdit Integration**: Seamless integration with EasyEdit for area selection
- **Command System**: Comprehensive command interface for area management
- **Area Visualization**: Visual representation of area boundaries
- **Copy & Paste**: Duplicate areas with all settings

### **User Experience**
- **Multi-language Support**: Built-in localization system
- **Permission-based Access**: Granular permission system for different area flags
- **Resource Pack Integration**: Enhanced visual experience with custom resource packs
- **Command Completion**: Smart command suggestions and auto-completion

## Requirements

- **PocketMine-MP**: API 5.0.0 or higher
- **EasyEdit**: Required dependency for area selection and management
- **InvMenu**: Required for GUI functionality (automatically installed if missing)

## Installation

1. **Download** the plugin from Poggit or compile from source
2. **Upload** the plugin to your server's `plugins` folder
3. **Restart** your server
4. **Configure** the plugin in `plugins/PrismArea/config.yml`
5. **Set permissions** for your staff members

## Commands

### Main Command: `/area`

| Subcommand | Description | Usage |
|------------|-------------|-------|
| `create <name>` | Create a new area from your current selection | `/area create spawn` |
| `delete <name>` | Delete an existing area | `/area delete spawn` |
| `list` | List all areas on the server | `/area list` |
| `info <name>` | Display detailed information about an area | `/area info spawn` |
| `edit <name>` | Open the area editor GUI | `/area edit spawn` |
| `copy <source> <destination>` | Copy an area with all settings | `/area copy spawn spawn2` |
| `visualize <name>` | Show area boundaries visually | `/area visualize spawn` |
| `select <name>` | Select an area for editing | `/area select spawn` |
| `unselect <name>` | Deselect an area | `/area unselect spawn` |
| `prioritize <target> <reference>` | Set area priority relative to another | `/area prioritize spawn lobby` |

## Permissions

### Main Permission
- `prism.area.*` - Access to all area management features (default: op)

### Area-Specific Permissions
Each area has individual permissions for each flag:
- `prism.area.<areaname>.flag.<flagname>` - Control specific flags for an area
- `prism.area.<areaname>.subflag.<subflagname>` - Control specific sub-flags for an area

## Configuration

### `config.yml`
```yaml
# Prism Studio - Area
# This file is used to configure the Area of Prism Studio.

# Uses an additional client-side mechanism
use-abilities: true
command-completion: true
send-messages: true
```

### Configuration Options
- **`use-abilities`**: Enable/disable client-side ability system (default: true)
- **`command-completion`**: Enable/disable command auto-completion (default: true)
- **`send-messages`**: Enable/disable plugin messages (default: true)

## Area Flags

### Player Flags
- `PLAYER_BREAK` - Control block breaking
- `PLAYER_BUILD` - Control block placement
- `PLAYER_USE_ITEMS` - Control item usage
- `PLAYER_PICKUP` - Control item pickup
- `PLAYER_DROP` - Control item dropping
- `PLAYER_SWIMMING` - Control swimming ability
- `PLAYER_EMOTE` - Control emote usage
- `PLAYER_CONTAINERS` - Control container interactions
- `PLAYER_INTERACT` - Control general interactions

### World Flags
- `WORLD_DAMAGE` - Control environmental damage
- `WORLD_ATTACK_PLAYERS` - Control mob attacks on players
- `WORLD_ATTACK_MOBS` - Control mob attacks on other mobs
- `WORLD_INTERACT_PLAYERS` - Control world interactions with players
- `WORLD_INTERACT_MOBS` - Control world interactions with mobs
- `WORLD_REGENERATION` - Control health regeneration
- `WORLD_HUNGER_LOSS` - Control hunger mechanics

### General Flags
- `LEFT_CLICK` - Control left-click actions
- `RIGHT_CLICK` - Control right-click actions
- `BLOCK` - Control general block interactions

## Usage Examples

### Creating a Protected Spawn Area
1. Use EasyEdit to select the spawn region
2. Run `/area create spawn`
3. Use `/area edit spawn` to configure flags
4. Set appropriate permissions for players

### Creating a Build World
1. Select the build world boundaries
2. Run `/area create build_world`
3. Configure flags to allow building but restrict other actions
4. Set priority to ensure it takes precedence

### Managing Multiple Areas
1. Create areas with different priorities
2. Use `/area list` to view all areas
3. Use `/area prioritize` to adjust area hierarchy
4. Use `/area copy` to duplicate successful area configurations

## Support

- **Issues**: Report bugs and feature requests on the project's issue tracker
- **Documentation**: Check the source code for detailed API documentation
- **Community**: Join the PocketMine-MP community for support

## License
This project is licensed under the [GNU General Public License v3.0](LICENSE) – see the LICENSE file for details.

## Credits

- **Author**: Zwuiix
- **Dependencies**: EasyEdit, InvMenu
- **PocketMine-MP**: The server software that makes this plugin possible