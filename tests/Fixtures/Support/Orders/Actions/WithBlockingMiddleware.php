<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Tests\Fixtures\Support\Orders\Middleware\Blocks;

final class WithBlockingMiddleware implements Action
{
    use AsAction;

    public const HANDLE = self::class.'::handle';

    public function prepare(): void
    {
        $this->through(new Blocks);
    }

    public function handle(): string
    {
        Context::push(Action::class, self::HANDLE);

        return self::HANDLE;
    }
}
