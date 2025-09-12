<?php

declare(strict_types=1);

namespace Tests\Fixtures\PHPStan;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class ValidAction implements Action
{
    use AsAction;

    public function execute()
    {
        return null;
    }
}
