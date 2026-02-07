<?php

declare(strict_types=1);

namespace Tests\Fixtures\Variations;

use Illuminate\Foundation\Queue\Queueable;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class ActionWithQueueable implements Action
{
    use AsAction;
    use Queueable;

    public function handle(): string
    {
        return 'processed';
    }
}
