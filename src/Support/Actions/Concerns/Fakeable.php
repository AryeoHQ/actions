<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Support\Facades\Bus;
use Support\Actions\Testing\Fakes\Action;

trait Fakeable
{
    public static function fake(): Action
    {
        return Action::make(static::class);
    }

    public static function assertFired(callable|int|null $callback = null): void
    {
        Bus::assertDispatched(static::class, $callback);
    }

    public static function assertNotFired(callable|null $callback = null): void
    {
        Bus::assertNotDispatched(static::class, $callback);
    }

    public static function assertFiredTimes(int $times = 1): void
    {
        Bus::assertDispatchedTimes(static::class, $times);
    }
}
