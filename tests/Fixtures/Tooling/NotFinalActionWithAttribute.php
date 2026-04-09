<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

#[\AllowDynamicProperties]
class NotFinalActionWithAttribute implements Action
{
    use AsAction;

    public function handle()
    {
        return null;
    }
}
