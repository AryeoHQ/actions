<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders;

use Illuminate\Support\Facades\Context;
use Support\Actions\Contracts\Action;

final class NonAction
{
    public function handle(): void
    {
        Context::push(Action::class, self::class);
    }
}
