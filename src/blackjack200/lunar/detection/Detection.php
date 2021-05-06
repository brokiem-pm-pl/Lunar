<?php
declare(strict_types=1);

namespace blackjack200\lunar\detection;


use blackjack200\lunar\user\User;

interface Detection {
    /**
     * This is the construct of Detection
     * @param mixed $data Configure of the Detection
     */
    public function __construct(User $user, string $name, string $fmt, ?string $webhookFmt, $data);

    /**
     * This method is trigger by DetectionTrigger
     * @param mixed $data
     * @see DetectionTrigger
     */
    public function check(...$data): void;

    public function alert(string $message): void;

    public function fail(string $message): void;

    public function debug(string $message): void;

    public function finalize(): void;

    public function getName(): string;
}