<?php

namespace PrismArea\listener;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\player\Player;
use PrismArea\area\AreaManager;
use PrismArea\Loader;
use PrismArea\session\SessionManager;
use PrismArea\types\AreaFlag;
use PrismArea\types\AreaSubFlag;
use PrismArea\types\Translatable;

class WorldListener implements Listener
{
    protected SessionManager $sessionManager;

    /**
     * PlayerListener constructor.
     *
     * @param Loader $loader
     * @param AreaManager $areaManager
     */
    public function __construct(
        protected readonly Loader      $loader,
        protected readonly AreaManager $areaManager
    ) {
        $this->sessionManager = SessionManager::getInstance();
    }

    /**
     * @param EntityDamageByEntityEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleEntityDamageByEntity(EntityDamageByEntityEvent $ev): void
    {
        $entity = $ev->getEntity();
        $damager = $ev->getDamager();

        // Check if the entity is a player or a mob
        $area = $this->areaManager->find($entity->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the area allows damage to entities
        if($entity instanceof Player) {
            if(!$area->hasFlag(AreaFlag::WORLD_ATTACK_PLAYERS)) {
                if($damager instanceof Player) {
                    $this->sessionManager->getOrCreate($damager)
                        ->sendMessage(Translatable::WORLD_ATTACK_PLAYERS_DENIED, $entity->getName());
                }
                $ev->cancel();
                return;
            }
        } else if(!$area->hasFlag(AreaFlag::WORLD_ATTACK_MOBS)) {
            // Cancel damage to mobs if the area does not allow it
            if($damager instanceof Player) {
                $this->sessionManager->getOrCreate($damager)
                    ->sendMessage(Translatable::WORLD_ATTACK_MOBS_DENIED);
            }
            $ev->cancel();
            return;
        }
    }

    /**
     * @param EntityDamageEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleEntityDamage(EntityDamageEvent $ev): void
    {
        $entity = $ev->getEntity();

        // Check if the entity is a player or a mob
        $area = $this->areaManager->find($entity->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the area allows damage to entities
        if(!$area->hasSubFlag(AreaSubFlag::WORLD_DAMAGE_FALL) && $ev->getCause() === EntityDamageEvent::CAUSE_FALL) {
            $ev->cancel();
            return; // Area does not allow fall damage
        }

        // Check if the area allows damage in general
        if($area->hasFlag(AreaFlag::WORLD_DAMAGE)) {
            return; // Area allows damage, nothing to do
        }

        $ev->cancel();
    }

    /**
     * @param PlayerEntityInteractEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handlePlayerInteractEntityEvent(PlayerEntityInteractEvent $ev): void
    {
        $player = $ev->getPlayer();
        $entity = $ev->getEntity();

        $area = $this->areaManager->find($entity->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the area allows interaction with players or mobs
        if($entity instanceof Player && !$area->can(AreaFlag::WORLD_INTERACT_PLAYERS, $player)) {
            $this->sessionManager->getOrCreate($player)
                ->sendMessage(Translatable::WORLD_INTERACT_PLAYERS_DENIED, $entity->getName());
            $ev->cancel();
            return; // Area does not allow interaction with players
        }

        // Check if the area allows interaction with mobs
        if(!$area->can(AreaFlag::WORLD_INTERACT_MOBS, $player)) {
            $this->sessionManager->getOrCreate($player)
                ->sendMessage(Translatable::WORLD_INTERACT_MOBS_DENIED);
            $ev->cancel();
            return; // Area does not allow interaction with mobs
        }
    }

    /**
     * @param EntityRegainHealthEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleEntityRegainHealth(EntityRegainHealthEvent $ev): void
    {
        $entity = $ev->getEntity();
        $cause = $ev->getRegainReason();

        // Check if the entity is a player or a mob
        $area = $this->areaManager->find($entity->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the area allows regeneration
        if($area->can(AreaFlag::WORLD_REGENERATION, $entity)) {
            return; // Area allows regeneration, nothing to do
        }

        // Check the cause of the health regain
        if($cause === EntityRegainHealthEvent::CAUSE_MAGIC || $cause === EntityRegainHealthEvent::CAUSE_CUSTOM) {
            return; // Magic or custom healing is allowed, nothing to do
        }

        $ev->cancel();
    }

    /**
     * @param PlayerExhaustEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleExhaust(PlayerExhaustEvent $ev): void
    {
        $player = $ev->getPlayer();

        // Check if the player is in an area
        $area = $this->areaManager->find($player->getPosition());
        if ($area === null) {
            return; // No area found, nothing to do
        }

        // Check if the area allows hunger loss
        if($area->can(AreaFlag::WORLD_HUNGER_LOSS, $player)) {
            return; // Area allows hunger loss, nothing to do
        }

        $player->getHungerManager()->setFood(20);
        $player->getHungerManager()->setSaturation(20);
    }
}