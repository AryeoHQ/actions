<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling;

final class CallingHandleDirectlyOnAction
{
    public function execute(): void
    {
        $action = ValidAction::make();
        $action->handle();
    }
}
