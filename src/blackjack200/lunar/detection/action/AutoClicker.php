<?php
declare(strict_types=1);

namespace blackjack200\lunar\detection\action;


use blackjack200\lunar\detection\DetectionBase;
use blackjack200\lunar\user\User;

class AutoClicker extends DetectionBase {
    /** @var int|float|mixed */
    protected $maxCPS;

    public function __construct(User $user, string $name, string $fmt, ?string $webhookFmt, $data) {
        parent::__construct($user, $name, $fmt, $webhookFmt, $data);
        $this->maxCPS = $this->getConfiguration()->getExtraData()->MaxCPS;
    }

    /**
     * @param mixed $data
     */
    public function check(...$data): void {
        $CPS = $this->getUser()->CPS;
        if ($CPS >= $this->maxCPS) {
            $this->addVL(1, "CPS=$CPS");
            if ($this->overflowVL()) {
                $this->fail("CPS=$CPS");
            }
        } else {
            $this->VL *= $this->getConfiguration()->getReward();
        }
    }
}