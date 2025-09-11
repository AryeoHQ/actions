<?php

namespace Tests\Fixtures;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

class TestActionWithArgs implements Action
{
    use AsAction;

    public function execute(string $foo): string
    {
        return $foo;
    }
}