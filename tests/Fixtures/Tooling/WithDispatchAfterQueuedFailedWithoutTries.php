<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling;

use Support\Actions\Attributes\DispatchAfterQueuedFailed;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

#[DispatchAfterQueuedFailed]
final class WithDispatchAfterQueuedFailedWithoutTries implements Action
{
    use AsAction;

    public function handle(): void {}
}
