<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class ActionWithMiddlewareMethod implements Action
{
    use AsAction;

    /** @return array<int, class-string> */
    public function middleware(): array
    {
        return [];
    }

    public function handle(): string
    {
        return 'processed';
    }
}
