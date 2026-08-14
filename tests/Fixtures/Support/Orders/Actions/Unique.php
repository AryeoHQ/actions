<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class Unique implements Action, ShouldBeUnique
{
    use AsAction;

    public const KEY = 'orders';

    public const HANDLE = self::class.'::handle';

    public function uniqueId(): string
    {
        return self::KEY;
    }

    public function handle(): string
    {
        Context::push(Action::class, self::HANDLE);

        return self::HANDLE;
    }
}
