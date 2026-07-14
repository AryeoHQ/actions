<?php

declare(strict_types=1);

namespace Support\Actions\Middleware\Lifecycle;

use Support\Actions\Attributes\DispatchAfterQueuedFailed;
use Support\Actions\Middleware\Lifecycle\Contracts\Lifecycle;
use Throwable;

class RunDispatchAfterQueuedFailed implements Lifecycle
{
    public function handle(object $command, callable $next): mixed
    {
        $dispatchable = (clone $command)->clearJob();

        try {
            $result = $next($command);
        } catch (Throwable $throwable) {
            $this->dispatch($command, $dispatchable, threw: true);

            throw $throwable;
        }

        $this->dispatch($command, $dispatchable, threw: false);

        return $result;
    }

    private function dispatch(object $command, object $dispatchable, bool $threw): void
    {
        when(
            $this->shouldDispatch($command, $threw),
            fn () => rescue(fn () => $dispatchable->dispatch(), report: true) // @phpstan-ignore argument.templateType
        );
    }

    private function shouldDispatch(object $command, bool $threw): bool
    {
        return $command->runningInQueue
            && $this->failedTerminally($command, $threw)
            && $command->declares(DispatchAfterQueuedFailed::class);
    }

    /**
     * A queued run fails terminally by declaration — fail() marked the job as
     * failed, whether it then returned or threw — or by exhaustion, when a
     * thrown exception consumes the final attempt.
     */
    private function failedTerminally(object $command, bool $threw): bool
    {
        return $command->failed || ($threw && $command->attemptsExhausted);
    }
}
