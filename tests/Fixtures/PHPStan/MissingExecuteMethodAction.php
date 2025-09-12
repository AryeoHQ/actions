<?php

namespace Tests\Fixtures\PHPStan;

use Support\Actions\Contracts\Action;
use Support\Actions\Concerns\AsAction;

final class MissingExecuteMethodAction implements Action
{
    use AsAction;

    public function someOtherMethod(): void
    {
        // Implementation
    }
}
