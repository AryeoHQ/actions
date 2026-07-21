<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Middleware;

final class Blocks
{
    public function handle(object $command, callable $next): void
    {
        // Intentionally does not call $next, preventing the action from running.
    }
}
