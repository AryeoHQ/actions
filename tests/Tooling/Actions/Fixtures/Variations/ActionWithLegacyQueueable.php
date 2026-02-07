<?php

declare(strict_types=1);

namespace Tests\Fixtures\Variations;

use Illuminate\Bus\Queueable;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class ActionWithLegacyQueueable implements Action
{
    use AsAction;
    use Queueable;

    public function handle(): string
    {
        return 'processed';
    }
}
