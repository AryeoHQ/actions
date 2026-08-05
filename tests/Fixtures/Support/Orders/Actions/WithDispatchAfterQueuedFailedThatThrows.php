<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use LogicException;
use RuntimeException;
use Support\Actions\Attributes\DispatchAfterQueuedFailed;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Throwable;

#[DispatchAfterQueuedFailed]
final class WithDispatchAfterQueuedFailedThatThrows implements Action
{
    use AsAction;

    public int $tries = 1;

    public function handle(): never
    {
        throw new RuntimeException;
    }

    public function failed(Throwable $exception): never
    {
        throw new LogicException;
    }
}
