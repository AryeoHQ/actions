<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Context;
use Support\Actions\Bus\Exceptions\Prevented;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class UniqueThatCallsItself implements Action, ShouldBeUnique
{
    use AsAction;

    public const KEY = 'orders';

    public const HANDLE = self::class.'::handle';

    public const PREVENTED = self::class.'::prevented';

    public function uniqueId(): string
    {
        return self::KEY;
    }

    public function handle(): void
    {
        Context::push(Action::class, self::HANDLE);

        // This run holds the lock, so a second now() on the same key must be prevented.
        try {
            self::make()->now();
        } catch (Prevented) {
            Context::push(Action::class, self::PREVENTED);
        }
    }
}
