<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use RuntimeException;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class UniqueThatFails implements Action, ShouldBeUnique
{
    use AsAction;

    public const KEY = 'orders';

    public function uniqueId(): string
    {
        return self::KEY;
    }

    public function handle(): never
    {
        throw new RuntimeException('failure');
    }
}
