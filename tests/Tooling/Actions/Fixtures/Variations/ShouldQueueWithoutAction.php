<?php

declare(strict_types=1);

namespace Tests\Fixtures\Variations;

use Illuminate\Contracts\Queue\ShouldQueue;

final class ShouldQueueWithoutAction implements ShouldQueue
{
    public function handle(): string
    {
        return 'processed';
    }
}
