<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Support\Actions\Bus\Invocation;
use Support\Actions\Middleware\Lifecycle\Contracts\Lifecycle;

trait HasLifecycle
{
    public function prepareFor(Invocation $via): static
    {
        $this->clearJob();

        when(
            method_exists($this, 'prepare'), // @phpstan-ignore function.impossibleType, function.alreadyNarrowedType
            fn () => call_user_func([$this, 'prepare']) // @phpstan-ignore argument.type
        );

        return $this->throughLifecycleMiddleware($via->middleware());
    }

    /**
     * @param  array<string>  $classes
     */
    private function throughLifecycleMiddleware(array $classes): static
    {
        return $this->through([
            ...$classes,
            ...$this->removeLifecycleMiddleware(),
        ]);
    }

    /**
     * @return array<mixed>
     */
    private function removeLifecycleMiddleware(): array
    {
        return array_filter($this->middleware, fn ($middleware) => match (true) {
            is_string($middleware) => ! is_subclass_of($middleware, Lifecycle::class),
            is_object($middleware) => ! $middleware instanceof Lifecycle,
            default => true,
        });
    }
}
