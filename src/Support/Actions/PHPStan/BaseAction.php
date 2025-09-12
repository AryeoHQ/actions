<?php

declare(strict_types=1);

namespace Support\Actions\PHPStan;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

/**
 * Not extendable. Example class for static analysis only.
 */
final class BaseAction implements Action
{
    use AsAction;

    public function execute(): void {}
}
