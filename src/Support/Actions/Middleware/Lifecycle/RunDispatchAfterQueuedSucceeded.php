<?php

declare(strict_types=1);

namespace Support\Actions\Middleware\Lifecycle;

use Support\Actions\Attributes\DispatchAfterQueuedSucceeded;
use Support\Actions\Middleware\Lifecycle\Contracts\Lifecycle;

class RunDispatchAfterQueuedSucceeded implements Lifecycle
{
    public function handle(object $command, callable $next): mixed
    {
        $dispatchable = (clone $command)->clearJob();

        return tap(
            $next($command),
            fn () => $this->dispatch($command, $dispatchable)
        );
    }

    private function dispatch(object $command, object $dispatchable): void
    {
        when(
            $this->shouldDispatch($command),
            fn () => rescue(fn () => $dispatchable->dispatch(), report: true) // @phpstan-ignore argument.templateType
        );
    }

    private function shouldDispatch(object $command): bool
    {
        return $command->runningInQueue
            && ! $command->failedOrReleased
            && $command->declares(DispatchAfterQueuedSucceeded::class);
    }
}
