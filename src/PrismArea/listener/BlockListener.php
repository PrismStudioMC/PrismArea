<?php

namespace PrismArea\listener;

use pocketmine\block\Block;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockExplodeEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockMeltEvent;
use pocketmine\event\block\BlockPreExplodeEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\event\Listener;
use pocketmine\world\BlockTransaction;
use pocketmine\world\Position;
use PrismArea\area\AreaManager;
use PrismArea\Loader;
use PrismArea\timings\TimingsManager;
use PrismArea\types\AreaFlag;
use PrismArea\types\AreaSubFlag;

class BlockListener implements Listener
{
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
    }

    /**
     * @param BlockBurnEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockBurn(BlockBurnEvent $ev): void
    {
        $this->processFlag($ev->getBlock()->getPosition(), AreaSubFlag::BLOCK_BURN, $ev);
    }

    /**
     * @param BlockSpreadEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockSpread(BlockSpreadEvent $ev): void
    {
        $this->processFlag($ev->getBlock()->getPosition(), AreaSubFlag::BLOCK_SPREAD, $ev);
    }

    /**
     * @param LeavesDecayEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleLeaveDecay(LeavesDecayEvent $ev): void
    {
        $this->processFlag($ev->getBlock()->getPosition(), AreaSubFlag::BLOCK_LEAVES_DECAY, $ev);
    }

    /**
     * @param BlockGrowEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockGrow(BlockGrowEvent $ev): void
    {
        $this->processFlag($ev->getBlock()->getPosition(), AreaSubFlag::BLOCK_GROW, $ev);
    }

    /**
     * @param BlockMeltEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockMelt(BlockMeltEvent $ev): void
    {
        $this->processFlag($ev->getBlock()->getPosition(), AreaSubFlag::BLOCK_MELT, $ev);
    }

    /**
     * @param BlockUpdateEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockUpdate(BlockUpdateEvent $ev): void
    {
        $this->processFlag($ev->getBlock()->getPosition(), AreaFlag::BLOCK, $ev);
    }

    /**
     * @param SignChangeEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleSigneChange(SignChangeEvent $ev): void
    {
        $player = $ev->getPlayer();
        $sign = $ev->getSign();

        // Check if in an area
        $area = $this->areaManager->find($sign->getPosition());
        if ($area === null) {
            return; // Not in any area, nothing to do
        }

        // Check if the area has the BLOCK_SIGN_CHANGE flag
        if ($area->can(AreaSubFlag::BLOCK_SIGN_CHANGE, $player, $sign->getPosition())) {
            return; // Allow sign change in this area
        }

        // Cancel the sign change
        $ev->cancel();
    }

    /**
     * @param BlockFormEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockForm(BlockFormEvent $ev): void
    {
        $this->processFlag($ev->getBlock()->getPosition(), AreaSubFlag::BLOCK_FORM, $ev);
    }

    /**
     * @param StructureGrowEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockStructureGrow(StructureGrowEvent $ev): void
    {
        $transaction = $ev->getTransaction();
        $newBlocks = [];

        // Process the blocks in the transaction
        $this->processBlocks($transaction->getBlocks(), $newBlocks, true);

        // Update the transaction with the new blocks
        $reflectionClass = new \ReflectionClass(BlockTransaction::class);
        $reflectionClass->getProperty("blocks")->setValue($transaction, $newBlocks);
    }

    /**
     * @param BlockPreExplodeEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockPreExplode(BlockPreExplodeEvent $ev): void
    {
        $this->processFlag($ev->getBlock()->getPosition(), AreaSubFlag::BLOCK_EXPLOSION, $ev);
    }

    /**
     * @param EntityPreExplodeEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleEntityPreExplode(EntityPreExplodeEvent $ev): void
    {
        $this->processFlag($ev->getEntity()->getPosition(), AreaSubFlag::BLOCK_EXPLOSION, $ev);
    }

    /**
     * @param BlockExplodeEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleBlockExplode(BlockExplodeEvent $ev): void
    {
        $blocks = $ev->getAffectedBlocks();
        $ignitions = $ev->getIgnitions();

        $newBlocks = [];
        $newIgnitions = [];

        $this->processBlocks($blocks, $newBlocks);
        $this->processBlocks($ignitions, $newIgnitions);

        $ev->setAffectedBlocks($newBlocks);
        $ev->setIgnitions($newIgnitions);
    }

    /**
     * @param EntityExplodeEvent $ev
     * @return void
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function handleEntityExplode(EntityExplodeEvent $ev): void
    {
        $blocks = $ev->getBlockList();
        $ignitions = $ev->getIgnitions();

        $newBlocks = [];
        $newIgnitions = [];

        $this->processBlocks($blocks, $newBlocks);
        $this->processBlocks($ignitions, $newIgnitions);

        $ev->setBlockList($newBlocks);
        $ev->setIgnitions($newIgnitions);
    }

    /**
     * @param Position $position
     * @param AreaFlag|AreaSubFlag $flag
     * @param Cancellable $ev
     * @return void
     */
    private function processFlag(Position $position, AreaFlag|AreaSubFlag $flag, Cancellable $ev): void
    {
        // Check if in an area
        $area = $this->areaManager->find($position);
        if ($area === null) {
            return; // Not in any area, nothing to do
        }

        // Check if the area has the specified flag
        if ($flag instanceof AreaFlag && $area->hasFlag($flag)) {
            return; // Allow the block action in this area
        } elseif ($flag instanceof AreaSubFlag && $area->hasSubFlag($flag)) {
            return; // Allow the block action in this area
        }

        // Cancel the block action
        $ev->cancel();
    }

    /**
     * @param array $blocks
     * @param array $data
     * @param bool $transaction
     * @return void
     */
    private function processBlocks(array $blocks, array &$data, bool $transaction = false): void
    {
        $processor = function (Block $block) use (&$data) {
            $added = true;
            try {
                // Check if in an area
                $area = $this->areaManager->find($block->getPosition());
                if ($area === null) {
                    return; // Not in any area, nothing to do
                }

                // Check if the area has the BLOCK_EXPLODE flag
                if ($area->hasSubFlag(AreaSubFlag::BLOCK_EXPLOSION)) {
                    return; // Allow explosion effects in this area
                }

                $added = false; // Cancel the block effect
            } finally {
                if ($added) {
                    $data[] = $block; // Keep the block if not cancelled
                }
            }
        };

        $timings = TimingsManager::getInstance()->getBlocksProcessing();
        $timings->startTiming(); // Start timing the block processing
        try {
            if ($transaction) {
                foreach ($blocks as $k => [, , , $block]) {
                    $processor($block);
                }
            } else {
                foreach ($blocks as $k => $block) {
                    $processor($block);
                }
            }
        } finally {
            $timings->stopTiming(); // Stop timing the block processing
        }
    }
}
