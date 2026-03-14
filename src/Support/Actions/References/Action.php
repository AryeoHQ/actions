<?php

declare(strict_types=1);

namespace Support\Actions\References;

use Illuminate\Support\Stringable;
use Tooling\GeneratorCommands\References\GenericClass;

class Action extends GenericClass
{
    public null|Stringable $subNamespace {
        get => str('Actions');
    }
}
