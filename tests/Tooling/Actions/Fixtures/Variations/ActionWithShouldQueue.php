<?php

declare(strict_types=1);

namespace Tests\Fixtures\Variations;

use Illuminate\Contracts\Queue\ShouldQueue;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class ActionWithShouldQueue implements Action, ShouldQueue
{
    use AsAction;

    public function handle(): string
    {
        return 'processed';
    }
}
