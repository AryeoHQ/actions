<?php

namespace Tests\Fixtures;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

class TestAction implements Action
{
    use AsAction;

    public function execute(): void {}
}
