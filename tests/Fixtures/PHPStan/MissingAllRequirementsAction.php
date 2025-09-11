<?php

declare(strict_types=1);

namespace Tests\Fixtures\PHPStan;

use Support\Actions\Contracts\Action;

class MissingAllRequirementsAction implements Action
{
    public static function make(): static
    {
        return new static;
    }

    public function someOtherMethod(): void
    {
        // Implementation
    }
}
