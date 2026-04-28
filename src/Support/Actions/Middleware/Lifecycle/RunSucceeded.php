<?php

declare(strict_types=1);

namespace Support\Actions\Middleware\Lifecycle;

use Support\Actions\Middleware\Lifecycle\Contracts\Lifecycle;

class RunSucceeded implements Lifecycle
{
    public function handle(object $command, callable $next): mixed
    {
        return tap(
            $next($command),
            fn () => when(
                method_exists($command, 'succeeded'),
                fn () => rescue(fn () => call_user_func([$command, 'succeeded']), report: true)
            )
        );
    }
}
