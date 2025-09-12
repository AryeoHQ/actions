<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Support\Fluent;
use Mockery;
use Mockery\Expectation;
use Mockery\ExpectationInterface;
use Mockery\MockInterface;

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
