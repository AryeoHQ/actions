<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Support\Facades\Context;
use RuntimeException;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class WithFailed implements Action
{
    use AsAction;

    public function handle(): never
    {
        throw new RuntimeException('Action failed intentionally');
    }

    public function failed(\Throwable $e): void
    {
        Context::push('execution_log', self::class);
    }
}
