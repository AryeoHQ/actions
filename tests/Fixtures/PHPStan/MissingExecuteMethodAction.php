<?php

namespace Tests\Fixtures\PHPStan;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class MissingExecuteMethodAction implements Action
{
    use AsAction;

    public function someOtherMethod(): void
    {
        // Implementation
    }
}
