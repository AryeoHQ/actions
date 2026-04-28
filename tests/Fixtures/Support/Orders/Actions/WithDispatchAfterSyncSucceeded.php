<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Support\Facades\Context;
use Support\Actions\Attributes\DispatchAfterSyncSucceeded;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

#[DispatchAfterSyncSucceeded]
final class WithDispatchAfterSyncSucceeded implements Action
{
    use AsAction;

    public const HANDLE = self::class.'::handle';

    public const SUCCEEDED = self::class.'::succeeded';

    public function handle(): string
    {
        Context::push(Action::class, self::HANDLE);

        return 'done';
    }

    public function succeeded(): void
    {
        Context::push(Action::class, self::SUCCEEDED);
    }
}
