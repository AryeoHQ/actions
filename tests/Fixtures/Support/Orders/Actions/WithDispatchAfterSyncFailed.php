<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Support\Facades\Context;
use RuntimeException;
use Support\Actions\Attributes\DispatchAfterSyncFailed;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

#[DispatchAfterSyncFailed]
final class WithDispatchAfterSyncFailed implements Action
{
    use AsAction;

    public const HANDLE = self::class.'::handle';

    public const FAILED = self::class.'::failed';

    public function handle(): never
    {
        Context::push(Action::class, self::HANDLE);

        throw new RuntimeException('Action failed intentionally');
    }

    public function failed(\Throwable $e): void
    {
        Context::push(Action::class, self::FAILED);
    }
}
