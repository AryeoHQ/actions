<?php

declare(strict_types=1);

namespace Support\Actions\Middleware\Lifecycle;

use ReflectionClass;
use Support\Actions\Attributes\DispatchAfterSyncSucceeded;
use Support\Actions\Middleware\Lifecycle\Contracts\Lifecycle;

class RunDispatchAfterSyncSucceeded implements Lifecycle
{
    public function handle(object $command, callable $next): mixed
    {
        $dispatchable = (clone $command)->clearJob();

        return tap(
            $next($command),
            fn () => when(
                (new ReflectionClass($command))->getAttributes(DispatchAfterSyncSucceeded::class) !== []
                    && ! $command->runningInQueue(),
                fn () => rescue(fn () => $dispatchable->dispatch(), report: true) // @phpstan-ignore argument.templateType
            )
        );
    }
}
