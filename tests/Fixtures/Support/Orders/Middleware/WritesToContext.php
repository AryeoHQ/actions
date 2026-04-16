<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Middleware;

use Illuminate\Support\Facades\Context;
use Support\Actions\Contracts\Action;

final class WritesToContext
{
    public function handle(object $command, callable $next): void
    {
        Context::push(Action::class, self::class);

        $next($command);
    }
}
