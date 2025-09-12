<?php

declare(strict_types=1);

namespace Support\Actions\PHPStan;

use Support\Actions\Contracts\Action;
use Support\Actions\Concerns\AsAction;

/**
 * Not extendable. Example class for static analysis only.
 */
final class BaseAction implements Action
{
    use AsAction;

    public function execute(): void
    {
    }
}