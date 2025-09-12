<?php

namespace Tests\Fixtures\PHPStan;

use Support\Actions\Contracts\Action;
use Support\Actions\Concerns\AsAction;

final class CompleteAction implements Action
{
    use AsAction;

    public function execute(string $input): string
    {
        return 'Processed: ' . $input;
    }
}
