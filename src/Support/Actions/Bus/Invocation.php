<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

use Support\Actions\Middleware\Lifecycle\RunDispatchAfterQueuedFailed;
use Support\Actions\Middleware\Lifecycle\RunDispatchAfterQueuedSucceeded;
use Support\Actions\Middleware\Lifecycle\RunDispatchAfterSyncFailed;
use Support\Actions\Middleware\Lifecycle\RunDispatchAfterSyncSucceeded;
use Support\Actions\Middleware\Lifecycle\RunFailed;
use Support\Actions\Middleware\Lifecycle\RunSucceeded;

enum Invocation
{
    case Now;

    case Dispatch;

    case Sync;

    /**
     * @return array<class-string>
     */
    public function middleware(): array
    {
        return match ($this) {
            self::Now => [
                RunSucceeded::class,
                RunFailed::class,
                RunDispatchAfterSyncSucceeded::class,
                RunDispatchAfterSyncFailed::class,
            ],
            self::Dispatch => [
                RunSucceeded::class,
                RunDispatchAfterQueuedSucceeded::class,
                RunDispatchAfterQueuedFailed::class,
            ],
            self::Sync => [
                RunSucceeded::class,
                RunDispatchAfterSyncSucceeded::class,
                RunDispatchAfterSyncFailed::class,
            ],
        };
    }
}
