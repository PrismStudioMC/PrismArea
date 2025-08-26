<?php

namespace PrismArea\area;

use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use PrismArea\Loader;
use PrismArea\types\AreaFlag;
use PrismArea\types\AreaSubFlag;

class Area implements \JsonSerializable
{
    /**
     * Area constructor.
     *
     * @param int $priority
     * @param string $name
     * @param World $world
     * @param AxisAlignedBB $aabb
     * @param array<string, bool> $flags
     * @param array<string, bool> $subFlags
     */
    public function __construct(
        private int                    $priority,
        private string                 $name,
        private readonly World         $world,
        private readonly AxisAlignedBB $aabb,
        private array                  $flags = [],
        private array                  $subFlags = []
    ) {
        $manager = PermissionManager::getInstance();
        foreach (AreaFlag::cases() as $k => $name) {
            $perm = "prism.area." . strtolower($this->name) . ".flag." . strtolower($name->name);
            $gPerm = "prism.flag." . strtolower($name->name);
            DefaultPermissions::registerPermission(
                new Permission($perm),
                [$manager->getPermission($gPerm)],
            );
            Loader::getInstance()->getLogger()->debug("Registered permission for area {$this->name} flag: " . strtolower($name->name));
        }

        foreach (AreaSubFlag::cases() as $k => $name) {
            $perm = "prism.area." . strtolower($this->name) . ".subflag." . strtolower($name->name);
            $gPerm = "prism.subflag." . strtolower($name->name);
            DefaultPermissions::registerPermission(
                new Permission($perm),
                [$manager->getPermission($gPerm)],
            );
            Loader::getInstance()->getLogger()->debug("Registered permission for area {$this->name} sub-flag: " . strtolower($name->name));
        }
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return World
     */
    public function getWorld(): World
    {
        return $this->world;
    }

    /**
     * @return AxisAlignedBB
     */
    public function getAABB(): AxisAlignedBB
    {
        return $this->aabb;
    }

    /**
     * @param AreaFlag|AreaSubFlag $flag
     * @param Entity $entity
     * @param Position|null $pos
     * @return bool
     */
    public function can(AreaFlag|AreaSubFlag $flag, Entity $entity, Position $pos = null): bool
    {
        // Check if the entity is a player
        if(is_null($pos) || $pos->equals(Vector3::zero())) {
            $pos = $entity->getPosition();
        }

        // Check if the entity is in the area
        if(!$this->aabb->isVectorInside($pos)) {
            return true;
        }

        if($entity instanceof Player) {
            // Check if the player has permission for the flag
            $perm = "prism.area." . strtolower($this->name) . "." . $flag instanceof AreaFlag ? "flag" : "subflag" . "." . strtolower($flag->name);
            if($entity->hasPermission($perm)) {
                return true; // Player has permission for the flag
            }

            // Check if the player has the global permission for the flag
            if($entity->isCreative(true)) {
                return true; // Creative players bypass area flags
            }
        }

        // Check if the flag is set
        if($flag instanceof AreaFlag) {
            return $this->hasFlag($flag);
        } else {
            return $this->hasSubFlag($flag);
        }
    }

    /**
     * @param AreaFlag $flag
     * @return bool
     */
    public function hasFlag(AreaFlag $flag): bool
    {
        return $this->flags[$flag->name] ?? true;
    }

    /**
     * @param AreaFlag $flag
     * @param bool $value
     */
    public function setFlag(AreaFlag $flag, bool $value = true): void
    {
        $this->flags[$flag->name] = $value;
    }

    /**
     * @return array
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * @param array $flags
     */
    public function setFlags(array $flags): void
    {
        $this->flags = $flags;
    }

    /**
     * @param AreaSubFlag $flag
     * @return bool
     */
    public function hasSubFlag(AreaSubFlag $flag): bool
    {
        return $this->subFlags[$flag->name] ?? true;
    }

    /**
     * @param AreaSubFlag $flag
     * @param bool $value
     */
    public function setSubFlag(AreaSubFlag $flag, bool $value = true): void
    {
        $this->subFlags[$flag->name] = $value;
    }

    /**
     * @return array
     */
    public function getSubFlags(): array
    {
        return $this->subFlags;
    }

    /**
     * @param array $subFlags
     */
    public function setSubFlags(array $subFlags): void
    {
        $this->subFlags = $subFlags;
    }

    /**
     * @param array $data
     * @return Area
     */
    public static function parse(array $data): Area
    {
        $name = $data["name"];

        // Validate the name
        $world = Server::getInstance()->getWorldManager()->getWorldByName($data["world"] ?? throw new \RuntimeException("World not specified in area data."));
        if($world === null) {
            throw new \RuntimeException("World '{$data["world"]}' not loaded.");
        }

        // Validate the AABB
        $aabb = new AxisAlignedBB(
            $data["aabb"]["minX"] ?? throw new \RuntimeException("AABB minX not specified in area data."),
            $data["aabb"]["minY"] ?? throw new \RuntimeException("AABB minY not specified in area data."),
            $data["aabb"]["minZ"] ?? throw new \RuntimeException("AABB minZ not specified in area data."),
            $data["aabb"]["maxX"] ?? throw new \RuntimeException("AABB maxX not specified in area data."),
            $data["aabb"]["maxY"] ?? throw new \RuntimeException("AABB maxY not specified in area data."),
            $data["aabb"]["maxZ"] ?? throw new \RuntimeException("AABB maxZ not specified in area data.")
        );

        // Validate flags and subFlags
        $flags = [];
        foreach ($data["flags"] ?? throw new \RuntimeException("Flags not specified in area data.") as $flag => $value) {
            $areaFlag = AreaFlag::fromString($flag);
            if ($areaFlag !== null) {
                $flags[$areaFlag->name] = $value;
            } else {
                throw new \RuntimeException("Invalid area flag: $flag");
            }
        }

        // Validate subFlags
        $subFlags = [];
        foreach ($data["subFlags"] ?? throw new \RuntimeException("SubFlags not specified in area data.") as $subFlag => $value) {
            $areaSubFlag = AreaSubFlag::fromString($subFlag);
            if ($areaSubFlag !== null) {
                $subFlags[$areaSubFlag->name] = $value;
            } else {
                throw new \RuntimeException("Invalid area sub-flag: $subFlag");
            }
        }

        return new self(
            $data["priority"] ?? 0,
            $name,
            $world,
            $aabb,
            $flags,
            $subFlags
        );
    }

    /**
     * Serializes the area to an array for JSON representation.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            "priority" => $this->priority,
            "name" => $this->name,
            "world" => $this->world->getFolderName(),
            "aabb" => [
                "minX" => $this->aabb->minX,
                "minY" => $this->aabb->minY,
                "minZ" => $this->aabb->minZ,
                "maxX" => $this->aabb->maxX,
                "maxY" => $this->aabb->maxY,
                "maxZ" => $this->aabb->maxZ
            ],
            "flags" => $this->flags,
            "subFlags" => $this->subFlags
        ];
    }
}