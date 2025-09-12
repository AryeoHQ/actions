<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Mockery;
use Mockery\MockInterface;
use Illuminate\Support\Fluent;
use Mockery\HigherOrderMessage;
use Mockery\ExpectationInterface;

trait AsAction
{
    public static function make(): static
    {
        /** @var static */
        return app(static::class);
    }

    public static function mock(): MockInterface
    {
        return tap(
            Mockery::getContainer()->mock(static::class),
            fn ($instance) => app()->instance(static::class, $instance)
        );
    }

    /**
     * Execute the action if the condition is true.
     */
    public function executeIf(bool $shouldExecute, mixed ...$arguments): mixed
    {
        return $shouldExecute
            ? $this->execute(...$arguments)
            : new Fluent;
    }

    public static function shouldExecute(): ExpectationInterface|HigherOrderMessage
    {
        return static::mock()->makePartial()->shouldReceive('execute');
    }
}
