<?php

declare(strict_types=1);

namespace Tests\Fixtures\Variations;

use Support\Actions\Concerns\AsAction;

final class MissingActionContractAction
{
    use AsAction;

    public function execute(): void
    {
        // Implementation
    }
}
