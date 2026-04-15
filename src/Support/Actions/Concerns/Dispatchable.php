<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\InteractsWithQueue;
use Support\Actions\Middleware\RunSucceededHook;

/** @method $this queueableThrough(mixed $middleware) */
trait Dispatchable
{
    use InteractsWithQueue;
    use Queueable {
        Queueable::through as private queueableThrough;
    }

    public function dispatch(): PendingDispatch
    {
        return new PendingDispatch(
            $this->throughRequiredMiddleware()
        );
    }

    /**
     * @param  array<object>|object  $middleware
     * @return $this
     */
    public function through($middleware): static
    {
        return $this->queueableThrough($middleware)->throughRequiredMiddleware();
    }

    /**
     * @return $this
     */
    private function throughRequiredMiddleware(): static
    {
        $required = [RunSucceededHook::class];

        $this->middleware = [
            ...array_filter($this->middleware, fn ($middleware) => match (true) {
                is_string($middleware) => ! in_array($middleware, $required, true),
                is_object($middleware) => ! in_array($middleware::class, $required, true),
                default => true,
            }),
            ...$required,
        ];

        return $this;
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
