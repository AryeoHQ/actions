<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders;

use Illuminate\Support\Facades\Context;

final class NonAction
{
    public function handle(): void
    {
        Context::push('execution_log', self::class);
    }
}
