<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling;

use Illuminate\Foundation\Queue\Queueable;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

#[\AllowDynamicProperties]
final class ActionWithQueueableAndAttribute implements Action
{
    use AsAction;
    use Queueable;

    public function handle(): string
    {
        return 'processed';
    }
}
