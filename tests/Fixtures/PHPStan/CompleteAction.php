<?php

namespace Tests\Fixtures\PHPStan;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class CompleteAction implements Action
{
    use AsAction;

    public function execute(string $input): string
    {
        return 'Processed: '.$input;
    }
}
