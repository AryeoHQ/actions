<?php

namespace Support\Actions\Concerns;

use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;
use Illuminate\Support\Fluent;
use Mockery\ExpectationInterface;

trait AsAction
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function mock(): MockInterface
    {
        $instance = static::make();

        if ($instance instanceof MockInterface) {
            return $instance;
        }

        return tap(
            Mockery::getContainer()->mock(static::class),
            fn ($instance) => app()->instance(static::class, $instance)
        );
    }

    /**
     * Execute the action if the condition is true.
     *
     * @param bool $shouldExecute
     * @param mixed ...$arguments
     * @return mixed
     */
    public function executeIf(bool $shouldExecute, mixed ...$arguments): mixed
    {
        return $shouldExecute
            ? $this->execute(...$arguments) 
            : new Fluent;
    }

    public static function shouldExecute(): ExpectationInterface|Expectation
    {
        return static::mock()->makePartial()->shouldReceive('execute');
    }
}
