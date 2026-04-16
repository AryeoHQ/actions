<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Support\Facades\Context;
use RuntimeException;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class WithFailedAndSucceeded implements Action
{
    use AsAction;

    public const SUCCEEDED = self::class.'::succeeded';

    public const FAILED = self::class.'::failed';

    public function handle(): never
    {
        throw new RuntimeException('Action failed intentionally');
    }

    public function succeeded(): void
    {
        Context::push(Action::class, self::SUCCEEDED);
    }

    public function failed(\Throwable $e): void
    {
        Context::push(Action::class, self::FAILED);
    }
}
