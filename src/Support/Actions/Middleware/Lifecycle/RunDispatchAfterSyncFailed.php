<?php

declare(strict_types=1);

namespace Support\Actions\Middleware\Lifecycle;

use Support\Actions\Attributes\DispatchAfterSyncFailed;
use Support\Actions\Middleware\Lifecycle\Contracts\Lifecycle;
use Throwable;

class RunDispatchAfterSyncFailed implements Lifecycle
{
    public function handle(object $command, callable $next): mixed
    {
        $dispatchable = (clone $command)->clearJob();

        try {
            return $next($command);
        } catch (Throwable $throwable) {
            $this->dispatch($command, $dispatchable);

            throw $throwable;
        }
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
        return $command->declares(DispatchAfterSyncFailed::class) && ! $command->runningInQueue;
    }
}
