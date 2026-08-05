<?php

declare(strict_types=1);

namespace Support\Actions\Pipeline;

use Closure;
use Illuminate\Pipeline\Pipeline;
use Support\Actions\Pipeline\Exceptions\Interrupted;

final class DetectsInterruption extends Pipeline
{
    protected function carry(): Closure
    {
        $carry = parent::carry();

        return fn ($stack, $pipe) => $this->interruptible($pipe, $carry, $stack);
    }

    /**
     * Wrap a single pipe so that, if it never passes control on, an `Interrupted` is thrown at
     * its own boundary rather than allowing the pipeline to resolve as if the run completed.
     *
     * @param  object|string  $pipe
     *
     * @throws \Support\Actions\Pipeline\Exceptions\Interrupted
     */
    private function interruptible($pipe, Closure $carry, Closure $stack): Closure
    {
        $trace = new class
        {
            public bool $continued = false;
        };

        $slice = $carry(
            fn ($passable) => with($trace->continued = true, fn () => $stack($passable)),
            $pipe
        );

        return fn ($passable) => tap(
            $slice($passable),
            fn () => throw_unless($trace->continued, fn () => Interrupted::by($this->passable::class, $this->classFor($pipe)))
        );
    }

    /**
     * @param  object|string  $pipe
     * @return class-string
     */
    private function classFor($pipe): string
    {
        return match (true) {
            is_object($pipe) => $pipe::class,
            default => $this->parsePipeString($pipe)[0],
        };
    }
}
