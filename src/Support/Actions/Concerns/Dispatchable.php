<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\ManuallyFailedException;
use Throwable;

trait Dispatchable
{
    use InteractsWithQueue;
    use Queueable;

    public function runningInQueue(): bool
    {
        return $this->job !== null && ! $this->job instanceof SyncJob;
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

        if ($this->runningInQueue()) {
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

    public function dispatch(): PendingDispatch
    {
        return new PendingDispatch($this);
    }

    public function dispatchIf(bool $condition): null|PendingDispatch
    {
        return match ($condition) {
            true => $this->dispatch(),
            false => null,
        };
    }

    public function dispatchUnless(bool $condition): null|PendingDispatch
    {
        return $this->dispatchIf(! $condition);
    }
}
