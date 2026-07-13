<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Throwable;

final class WithManualFail implements Action
{
    use AsAction;

    public const HANDLE = self::class.'::handle';

    public const AFTER_FAIL = self::class.'::after-fail';

    public const SUCCEEDED = self::class.'::succeeded';

    public const FAILED = self::class.'::failed';

    public readonly Throwable|string|null $exception;

    public function __construct(Throwable|string|null $exception = null)
    {
        $this->exception = $exception;
    }

    public function handle(): string
    {
        Context::push(Action::class, self::HANDLE);

        // Code after fail() runs in the queue (Laravel absorbs the failure and
        // execution continues) but never on now()/dispatchSync (fail() throws).
        // This fixture exercises both sides of that behavior.
        $this->fail($this->exception);

        Context::push(Action::class, self::AFTER_FAIL);

        return 'completed';
    }

    public function succeeded(): void
    {
        Context::push(Action::class, self::SUCCEEDED);
    }

    public function failed(Throwable $exception): void
    {
        Context::push(Action::class, self::FAILED);
        Context::add(self::FAILED, $exception);
    }
}
