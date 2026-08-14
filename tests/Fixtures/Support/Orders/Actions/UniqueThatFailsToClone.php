<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use LogicException;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class UniqueThatFailsToClone implements Action, ShouldBeUnique
{
    use AsAction;

    public const KEY = 'orders';

    public function uniqueId(): string
    {
        return self::KEY;
    }

    public function __clone(): void
    {
        throw new LogicException('failure');
    }

    public function handle(): void {}
}
