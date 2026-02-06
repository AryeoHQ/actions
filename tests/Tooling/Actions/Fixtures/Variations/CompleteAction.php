<?php

declare(strict_types=1);

namespace Tests\Fixtures\Variations;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class CompleteAction implements Action
{
    use AsAction;

    public function handle(string $input): string
    {
        return 'Processed: '.$input;
    }
}
