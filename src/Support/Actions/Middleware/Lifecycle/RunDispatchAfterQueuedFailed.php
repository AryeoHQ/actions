<?php

declare(strict_types=1);

namespace Support\Actions\Middleware\Lifecycle;

use ReflectionClass;
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
            $this->dispatchWhenFailedTerminally($command, $dispatchable, threw: true);

            throw $throwable;
        }

        $this->dispatchWhenFailedTerminally($command, $dispatchable, threw: false);

        return $result;
    }

    /**
     * A queued run fails terminally by declaration — fail() marked the job as
     * failed, whether it then returned or threw — or by exhaustion, when a
     * thrown exception consumes the final attempt.
     */
    private function dispatchWhenFailedTerminally(object $command, object $dispatchable, bool $threw): void
    {
        when(
            (new ReflectionClass($command))->getAttributes(DispatchAfterQueuedFailed::class) !== []
                && $command->runningInQueue()
                && ($command->job->hasFailed()
                    || ($threw && $command->job->maxTries() !== null && $command->attempts() >= $command->job->maxTries())),
            fn () => rescue(fn () => $dispatchable->dispatch(), report: true) // @phpstan-ignore argument.templateType
        );
    }
}
