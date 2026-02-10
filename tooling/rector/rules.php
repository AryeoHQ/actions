<?php

use Tooling\Actions\Rector\Rules\ActionCannotUseDispatchable;
use Tooling\Actions\Rector\Rules\ActionCannotUseQueueable;
use Tooling\Actions\Rector\Rules\ActionMustBeFinal;
use Tooling\Actions\Rector\Rules\ActionMustDefineHandleMethod;
use Tooling\Actions\Rector\Rules\ActionMustUseAsAction;
use Tooling\Actions\Rector\Rules\AsActionMustImplementAction;

return [
    ActionCannotUseDispatchable::class,
    ActionCannotUseQueueable::class,
    ActionMustBeFinal::class,
    ActionMustDefineHandleMethod::class,
    ActionMustUseAsAction::class,
    AsActionMustImplementAction::class,
];
