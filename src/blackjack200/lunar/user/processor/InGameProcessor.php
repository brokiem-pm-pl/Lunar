<?php


namespace blackjack200\lunar\user\processor;


use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

class InGameProcessor extends Processor {

    public function processClient(DataPacket $packet) : void {
		if (($packet instanceof InventoryTransactionPacket) && $packet->trData instanceof UseItemOnEntityTransactionData && $packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK) {
			$this->addClick();
		}
		if ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) {
			$this->addClick();
		}
	}

	public function addClick() : void {
		$CPS = &$this->getUser()->CPS;
		$CPS++;
	}

	public function check(...$data) : void {
		$usr = $this->getUser();
		foreach ($usr->getPlayer()->getEffects() as $effect) {
			if ($effect->getDuration() === 1) {
				$usr->getExpiredInfo()->set($effect->getId());
			}
		}
		$usr->CPS = 0;
	}
}