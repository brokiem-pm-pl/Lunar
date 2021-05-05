<?php
declare(strict_types=1);

namespace blackjack200\lunar\detection\movement\motion;


use blackjack200\lunar\detection\DetectionBase;
use pocketmine\entity\Effect;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class MotionB extends DetectionBase {
	public function handleClient(DataPacket $packet) : void {
		if ($packet instanceof MovePlayerPacket) {
			$user = $this->getUser();
			$info = $user->getMovementInfo();
			if (
				!$info->inVoid &&
                $info->checkFly &&
                !$user->getActionInfo()->isFlying &&
                $info->timeSinceTeleport() > 2 &&
                $user->timeSinceJoin() > 10 &&
                $user->getExpiredInfo()->duration('flight') > 2 &&
                $user->getExpiredInfo()->duration('checkFly') > 0.25
			) {
                $deltaY = $info->moveDelta->y;

                $modifierJump = $user->getEffectLevel(Effect::JUMP) * 0.1;
                $modifierVelocity = $info->timeSinceMotion() < 0.25 ? $info->velocity->y + 0.5 : 0.0;

                $maximum = 0.8 + $modifierJump + $modifierVelocity;

                if ($deltaY > $maximum) {
                    $this->addVL(1, "dy=$deltaY pred_max=$maximum");
                    $this->revertMovement();
                    if ($this->overflowVL()) {
                        $this->fail("dy=$deltaY pred_max=$maximum");
                    }
                }
            }
		}
	}
}