<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Throwable;

final class UniqueAndLifecycleHooks implements Action, ShouldBeUnique
{
    use AsAction;

    public const KEY = 'orders';

    public const HANDLE = self::class.'::handle';

    public const SUCCEEDED = self::class.'::succeeded';

    public const FAILED = self::class.'::failed';

    public function uniqueId(): string
    {
        return self::KEY;
    }

    public function handle(): void
    {
        Context::push(Action::class, self::HANDLE);
    }

    public function succeeded(): void
    {
        Context::push(Action::class, self::SUCCEEDED);
    }

    public function failed(Throwable $exception): void
    {
        Context::push(Action::class, self::FAILED);
    }
}
