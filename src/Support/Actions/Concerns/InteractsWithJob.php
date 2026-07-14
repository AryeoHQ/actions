<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\ManuallyFailedException;
use Throwable;

trait InteractsWithJob
{
    use InteractsWithQueue;

    public bool $runningInQueue {
        get => $this->job !== null && ! $this->job instanceof SyncJob;
    }

    public int $attempts {
        get => $this->attempts();
    }

    public bool $failed {
        get => $this->job?->hasFailed() === true;
    }

    public bool $released {
        get => $this->job?->isReleased() === true;
    }

    public bool $failedOrReleased {
        get => $this->failed || $this->released;
    }

    public bool $attemptsLimited {
        get {
            $maxTries = $this->job?->maxTries();

            return $maxTries !== null && $maxTries > 0;
        }
    }

    public bool $attemptsExhausted {
        get => $this->attemptsLimited && $this->attempts >= $this->job->maxTries();
    }

    /**
     * @throws \Throwable
     */
    public function fail(Throwable|string|null $exception = null): void
    {
        $exception = match (true) {
            is_string($exception) => new ManuallyFailedException($exception),
            $exception === null => new ManuallyFailedException,
            default => $exception,
        };

        if ($this->runningInQueue) {
            $this->job->fail($exception);

            return;
        }

        $this->job?->markAsFailed();

        throw $exception;
    }

    public function clearJob(): static
    {
        $this->job = null;

        return $this;
    }
}
