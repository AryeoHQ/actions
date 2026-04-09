<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling;

use Illuminate\Foundation\Bus\Dispatchable;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

#[\AllowDynamicProperties]
final class ActionWithDispatchableAndAttribute implements Action
{
    use AsAction;
    use Dispatchable;

    public function handle(): string
    {
        return 'processed';
    }
}
