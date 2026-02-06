<?php

use Tooling\Actions\Rector\Rules\ActionMustBeFinal;
use Tooling\Actions\Rector\Rules\ActionMustImplementShouldQueue;
use Tooling\Actions\Rector\Rules\AddActionToAsAction;
use Tooling\Actions\Rector\Rules\AddAsActionToAction;
use Tooling\Actions\Rector\Rules\ShouldQueueMustImplementAction;

return [
    ActionMustBeFinal::class,
    ActionMustImplementShouldQueue::class,
    AddActionToAsAction::class,
    AddAsActionToAction::class,
    ShouldQueueMustImplementAction::class,
];
