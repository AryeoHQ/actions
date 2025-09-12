<?php

namespace Tests\Fixtures\PHPStan;

use Support\Actions\Contracts\Action;
use Support\Actions\Concerns\AsAction;

final class ValidAction implements Action
{
    use AsAction;

    public function execute(): void
    {
        // Implementation
    }
}
