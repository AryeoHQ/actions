<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Support\Facades\Context;
use Support\Actions\Attributes\DispatchAfterSyncFailed;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

#[DispatchAfterSyncFailed]
final class WithManualFailAndDispatchAfterSyncFailed implements Action
{
    use AsAction;

    public const HANDLE = self::class.'::handle';

    public function handle(): void
    {
        Context::push(Action::class, self::HANDLE);

        $this->fail();
    }
}
