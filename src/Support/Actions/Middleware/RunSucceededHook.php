<?php

declare(strict_types=1);

namespace Support\Actions\Middleware;

class RunSucceededHook
{
    public function handle(object $command, callable $next): void
    {
        $next($command);

        if (method_exists($command, 'succeeded')) {
            rescue(fn () => $command->succeeded());
        }
    }
}
