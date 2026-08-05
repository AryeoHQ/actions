<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

trait InteractsWithChain
{
    public function clearChain(): static
    {
        $this->chained = [];
        $this->chainConnection = null;
        $this->chainQueue = null;
        $this->chainCatchCallbacks = null;

        return $this;
    }
}
