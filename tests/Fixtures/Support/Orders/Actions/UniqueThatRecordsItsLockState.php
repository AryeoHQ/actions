<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class UniqueThatRecordsItsLockState implements Action, ShouldBeUnique
{
    use AsAction;

    public const KEY = 'orders';

    public const LOCK_HELD = self::class.'::lock-held';

    public function uniqueId(): string
    {
        return self::KEY;
    }

    public function handle(): void
    {
        // Plain unique holds the lock through handle(), so this acquire should fail.
        $free = (new UniqueLock(app(Cache::class)))->acquire($this);

        Context::push(Action::class, $free ? self::class : self::LOCK_HELD);
    }
}
