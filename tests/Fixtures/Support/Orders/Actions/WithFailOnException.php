<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Queue\Middleware\FailOnException;
use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Throwable;

final class WithFailOnException implements Action
{
    use AsAction;

    public const HANDLE = self::class.'::handle';

    public const FAILED = self::class.'::failed';

    public function prepare(): void
    {
        $this->through([new FailOnException([AuthorizationException::class])]);
    }

    public function handle(): never
    {
        Context::push(Action::class, self::HANDLE);

        throw new AuthorizationException;
    }

    public function failed(Throwable $exception): void
    {
        Context::push(Action::class, self::FAILED);
    }
}
