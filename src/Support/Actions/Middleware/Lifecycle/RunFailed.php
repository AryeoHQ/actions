<?php

declare(strict_types=1);

namespace Support\Actions\Middleware\Lifecycle;

use Support\Actions\Middleware\Lifecycle\Contracts\Lifecycle;

class RunFailed implements Lifecycle
{
    public function handle(object $command, callable $next): mixed
    {
        try {
            return $next($command);
        } catch (\Throwable $throwable) {
            when(
                method_exists($command, 'failed'),
                fn () => rescue(fn () => call_user_func([$command, 'failed'], $throwable), report: true)
            );

            throw $throwable;
        }
    }
}
