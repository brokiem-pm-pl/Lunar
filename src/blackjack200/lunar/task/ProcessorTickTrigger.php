<?php
declare(strict_types=1);

namespace blackjack200\lunar\task;


use blackjack200\lunar\detection\action\NukerA;
use blackjack200\lunar\user\processor\MovementProcessor;
use blackjack200\lunar\user\UserManager;
use pocketmine\scheduler\Task;

class ProcessorTickTrigger extends Task {
    public function onRun(int $currentTick): void {
        foreach (UserManager::getUsers() as $user) {
            $user->trigger(NukerA::class);
            $user->triggerProcessor(MovementProcessor::class);
        }
    }
}