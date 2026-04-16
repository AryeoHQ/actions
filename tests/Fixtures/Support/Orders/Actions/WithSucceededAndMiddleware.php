<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Tests\Fixtures\Support\Orders\Middleware\WritesToContextBidirectional;

final class WithSucceededAndMiddleware implements Action
{
    use AsAction;

    public const HANDLE = self::class.'::handle';

    public const SUCCEEDED = self::class.'::succeeded';

    public function prepare(): void
    {
        $this->through(WritesToContextBidirectional::class);
    }

    public function handle(): void
    {
        Context::push(Action::class, self::HANDLE);
    }

    public function succeeded(): void
    {
        Context::push(Action::class, self::SUCCEEDED);
    }
}
