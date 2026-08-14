<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class UniqueUntilProcessingThatRecordsItsLockState implements Action, ShouldBeUniqueUntilProcessing
{
    use AsAction;

    public const KEY = 'orders';

    public const LOCK_FREE = self::class.'::lock-free';

    public function uniqueId(): string
    {
        return self::KEY;
    }

    public function handle(): void
    {
        // A separate acquire from inside handle() proves whether the lock is already released.
        // Until-processing releases at the seam, so this should succeed.
        $free = (new UniqueLock(app(Cache::class)))->acquire($this);

        Context::push(Action::class, $free ? self::LOCK_FREE : self::class);
    }
}
