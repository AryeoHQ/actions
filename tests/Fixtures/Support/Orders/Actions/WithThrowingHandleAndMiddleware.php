<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use LogicException;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Tests\Fixtures\Support\Orders\Middleware\WritesToContext;

final class WithThrowingHandleAndMiddleware implements Action
{
    use AsAction;

    public function prepare(): void
    {
        $this->through(WritesToContext::class);
    }

    public function handle(): never
    {
        throw new LogicException;
    }
}
