<?php


namespace blackjack200\lunar\user\processor;


use blackjack200\lunar\user\info\PlayerMovementInfo;
use blackjack200\lunar\user\User;
use blackjack200\lunar\utils\AABB;
use pocketmine\block\Block;
use pocketmine\block\Door;
use pocketmine\block\Ladder;
use pocketmine\block\Trapdoor;
use pocketmine\block\Vine;
use pocketmine\entity\Effect;
use pocketmine\item\ItemIds;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class MovementProcessor extends Processor {
	private const ICE = [ItemIds::ICE, ItemIds::PACKED_ICE, ItemIds::FROSTED_ICE];
	/** @var Vector3 */
	private static $emptyVector3;
	protected int $buffer = 0;

	public function __construct(User $user) {
		parent::__construct($user);
		if (self::$emptyVector3 === null) {
			self::$emptyVector3 = new Vector3();
		}
		$this->getUser()->getMovementInfo()->moveDelta = new Vector3();
		$user = $this->getUser();
		$info = $user->getMovementInfo();
		$info->location = $user->getPlayer()->asLocation();
		$this->updateLocation($info, $info->location);
		$this->updateMoveDelta($info);

	}

	public function updateLocation(PlayerMovementInfo $movementInfo, Location $location) : void {
		$movementInfo->lastLocation = $movementInfo->location;
		$movementInfo->location = $location;
	}

	public function updateMoveDelta(PlayerMovementInfo $movementInfo) : void {
		$movementInfo->lastMoveDelta = $movementInfo->moveDelta;
		$movementInfo->moveDelta = $movementInfo->location->subtract($movementInfo->lastLocation)->asVector3();
	}

	public function processClient(DataPacket $packet) : void {
		if ($packet instanceof MovePlayerPacket) {
			$user = $this->getUser();
			$info = $user->getMovementInfo();
			$player = $user->getPlayer();
			$location = Location::fromObject($packet->position->subtract(0, 1.62), $player->getLevel(), $packet->yaw, $packet->pitch);

			$this->updateLocation($info, $location);

			$this->updateMoveDelta($info);

			$dist = $info->moveDelta->lengthSquared();
			if ($dist > 0.006) {
				if ($this->buffer++ > 4) {
					$this->buffer = 0;
					$info->locationHistory->push($player->asLocation());
				}
				$AABB = AABB::fromPosition($location)->expandedCopy(0.5, 0.2, 0.5);
				$verticalBlocks = AABB::getCollisionBlocks($location->getLevel(), $AABB);
				$info->lastOnGround = $info->onGround;
				$info->onGround = count($player->getLevelNonNull()->getCollisionBlocks($AABB, true)) !== 0;

				$info->inVoid = $player->getY() < -15;
				$info->checkFly = !$player->isImmobile() && !$player->hasEffect(Effect::LEVITATION);
				foreach ($verticalBlocks as $block) {
					if (!$info->onGround) {
						$info->onGround = true;
					}
					$id = $block->getId();
					if (in_array($id, self::ICE, true)) {
						$info->onIce = true;
						continue;
					}

					if (
						$id === Block::SLIME_BLOCK ||
						$id === Block::COBWEB ||
						$block instanceof Door ||
						$block instanceof Trapdoor ||
						$block instanceof Vine ||
						$block instanceof Ladder ||
						$block->canClimb() ||
						$block->canBeFlowedInto()
					) {
						$info->checkFly = false;
						//$info->onGround = true;
						break;
					}
				}
				//$this->getUser()->getPlayer()->sendPopup('check=' . Boolean::btos($info->checkFly) . ' on=' . Boolean::btos($info->onGround) . ' tick=' . $info->inAirTick);
			}
		}
	}

	public function check(...$data) : void {
		$user = $this->getUser();
		$player = $user->getPlayer();
		if ($player->spawned) {
			$moveData = $user->getMovementInfo();
			if (!$moveData->onGround) {
				$moveData->inAirTick++;
				$moveData->onGroundTick = 0;
			} else {
				$moveData->inAirTick = 0;
				$moveData->onGroundTick++;
			}
			if ($player->isFlying()) {
				$moveData->flightTick++;
			} elseif ($moveData->flightTick !== 0) {
				$moveData->flightTick = 0;
				$user->getExpiredInfo()->set('flight');
			}

			if ($player->isSprinting()) {
				$moveData->sprintTick++;
			} elseif ($moveData->sprintTick !== 0) {
				$moveData->sprintTick = 0;
				$user->getExpiredInfo()->set('sprint');
			}
		}
	}
}