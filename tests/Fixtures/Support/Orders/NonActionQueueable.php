<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Context;

final class NonActionQueueable implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function handle(): void
    {
        Context::push('execution_log', self::class);
    }
}
