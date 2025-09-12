<?php

declare(strict_types=1);

namespace Tests\Fixtures\PHPStan;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

class NotFinalAction implements Action
{
    use AsAction;

    public function execute()
    {
        return null;
    }
}
