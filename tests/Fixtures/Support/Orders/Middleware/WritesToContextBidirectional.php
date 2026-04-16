<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Middleware;

use Illuminate\Support\Facades\Context;
use Support\Actions\Contracts\Action;

final class WritesToContextBidirectional
{
    public const IN = self::class.'::in';

    public const OUT = self::class.'::out';

    public function handle(object $command, callable $next): mixed
    {
        Context::push(Action::class, self::IN);

        $result = $next($command);

        Context::push(Action::class, self::OUT);

        return $result;
    }
}
