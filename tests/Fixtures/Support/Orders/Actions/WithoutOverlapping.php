<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Queue\Middleware\WithoutOverlapping as WithoutOverlappingMiddleware;
use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class WithoutOverlapping implements Action
{
    use AsAction;

    public const KEY = 'orders';

    public const HANDLE = self::class.'::handle';

    public function prepare(): void
    {
        $this->through(new WithoutOverlappingMiddleware(self::KEY));
    }

    public function handle(): string
    {
        Context::push(Action::class, self::HANDLE);

        return self::HANDLE;
    }
}
