<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Middleware;

use Illuminate\Support\Facades\Context;

final class WritesToContext
{
    public function handle(object $command, callable $next): void
    {
        Context::push('execution_log', self::class);

        $next($command);
    }
}
