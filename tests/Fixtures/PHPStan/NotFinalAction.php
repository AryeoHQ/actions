<?php

namespace Tests\Fixtures\PHPStan;

use Support\Actions\Contracts\Action;
use Support\Actions\Concerns\AsAction;

class NotFinalAction implements Action
{
    use AsAction;

    public function execute(): void
    {
        // Implementation
    }
}
