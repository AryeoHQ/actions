<?php

declare(strict_types=1);

namespace Support\Actions\Contracts;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\PendingDispatch;
use Support\Actions\Bus\Invocation;

interface Action extends ShouldQueue
{
    public static function make(mixed ...$arguments): static;

    public function dispatch(): PendingDispatch;

    public function now(): mixed;

    public function prepareFor(Invocation $via): static;

    public null|Invocation $invokedVia { get; }

    /**
     * @param  class-string  $attribute
     */
    public function declares(string $attribute): bool;

    public bool $runningInQueue { get; }

    public int $attempts { get; }

    public bool $failed { get; }

    public bool $released { get; }

    public bool $failedOrReleased { get; }

    public bool $attemptsLimited { get; }

    public bool $attemptsExhausted { get; }

    /**
     * @return $this
     */
    public function clearJob(): static;

    /**
     * @return $this
     */
    public function clearChain(): static;

    /**
     * @return $this
     */
    public function standalone(): static;

    /**
     * This implementation is provided to `AsAction` by `\Illuminate\Foundation\Queue\Queueable`.
     * Since we do not own that definition we cannot have this contract define runtime types.
     *
     * @param  array<array-key, object|class-string>|object|class-string  $middleware
     * @return $this
     */
    public function through($middleware);
}
