<?php
declare(strict_types=1);

namespace blackjack200\lunar\user\info;


use Ds\Map;

final class ExpiredInfo {
    private Map $data;

    public function __construct(int $size) {
        $this->data = new Map();
        $this->data->allocate($size);
    }

    /** @phpstan-ignore-next-line */
    public function set($k): void {
        $this->lazy($k);
        $this->data->put($k, microtime(true));
    }

    /** @phpstan-ignore-next-line */
    private function lazy($k): void {
        if (!$this->data->hasKey($k)) {
            $this->data->put($k, microtime(true));
        }
    }

    /** @phpstan-ignore-next-line */
    public function duration($k): float {
        $this->lazy($k);
        return microtime(true) - $this->data->get($k);
    }
}