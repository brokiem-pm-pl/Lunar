<?php
declare(strict_types=1);

namespace blackjack200\lunar\detection\action;


use blackjack200\lunar\detection\DetectionBase;
use blackjack200\lunar\user\User;
use pocketmine\entity\Effect;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

class FastBreakA extends DetectionBase {
    private float $breakTime;

    public function __construct(User $user, string $name, string $fmt, ?string $webhookFmt, $data) {
        parent::__construct($user, $name, $fmt, $webhookFmt, $data);
        $this->breakTime = floor(microtime(true) * 20);
    }

    /**
     * @param mixed $data
     */
    public function check(...$data): void {
        $this->impl($data[0]);
    }

    private function impl(BlockBreakEvent $event): void {
        if (!$event->getInstaBreak()) {
            $player = $event->getPlayer();
            $target = $event->getBlock();
            $item = $event->getItem();

            $expectedTime = ceil($target->getBreakTime($item) * 20);

            $effect = $player->getEffect(Effect::HASTE);
            if ($effect !== null) {
                $expectedTime *= 1 - (0.2 * $effect->getEffectLevel());
            }

            $effect = $player->getEffect(Effect::MINING_FATIGUE);
            if ($effect !== null) {
                $expectedTime *= 1 + (0.3 * $effect->getEffectLevel());
            }

            --$expectedTime; //1 tick compensation

            $actualTime = ceil(microtime(true) * 20) - $this->breakTime;

            if ($actualTime < $expectedTime) {
                $this->addVL(1, 'diff=' . number_format($actualTime - $expectedTime, 5));
                if ($this->overflowVL()) {
                    $this->fail('Try to break ' . $target->getName() . ' with tool= ' . $item->getVanillaName() . ' diff=' . number_format($actualTime - $expectedTime, 5));
                }
            }
        }
    }

    public function handleClient(DataPacket $packet): void {
        if ($packet instanceof PlayerActionPacket && $packet->action === PlayerActionPacket::ACTION_START_BREAK) {
            $this->breakTime = floor(microtime(true) * 20);
        }
    }
}