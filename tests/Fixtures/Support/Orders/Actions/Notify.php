<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class Notify implements Action
{
    use AsAction;

    public function handle(): string
    {
        return 'notified';
    }
}
