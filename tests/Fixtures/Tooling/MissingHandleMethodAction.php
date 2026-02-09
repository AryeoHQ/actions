<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class MissingHandleMethodAction implements Action
{
    use AsAction;
}
