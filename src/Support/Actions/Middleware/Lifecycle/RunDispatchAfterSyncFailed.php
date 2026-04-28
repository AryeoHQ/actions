<?php

declare(strict_types=1);

namespace Support\Actions\Middleware\Lifecycle;

use ReflectionClass;
use Support\Actions\Attributes\DispatchAfterSyncFailed;
use Support\Actions\Middleware\Lifecycle\Contracts\Lifecycle;

class RunDispatchAfterSyncFailed implements Lifecycle
{
    public function handle(object $command, callable $next): mixed
    {
        $dispatchable = (clone $command)->clearJob();

        try {
            return $next($command);
        } catch (\Throwable $throwable) {
            when(
                (new ReflectionClass($command))->getAttributes(DispatchAfterSyncFailed::class) !== []
                    && ! $command->runningInQueue(),
                fn () => rescue(fn () => $dispatchable->dispatch(), report: true) // @phpstan-ignore argument.templateType
            );

            throw $throwable;
        }
    }
}
