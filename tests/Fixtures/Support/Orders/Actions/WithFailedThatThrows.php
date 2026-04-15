<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use RuntimeException;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class WithFailedThatThrows implements Action
{
    use AsAction;

    public function handle(): never
    {
        throw new RuntimeException('Original exception');
    }

    public function failed(\Throwable $e): void
    {
        throw new \LogicException('failed() threw');
    }
}
