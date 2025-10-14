<?php

namespace Tests\Support\Actions;

use Tests\TestCase;
use Tests\Fixtures\TestClass;
use Illuminate\Support\Fluent;
use Tests\Fixtures\TestAction;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\TestActionWithArgs;

class ActionTest extends TestCase
{
    #[Test]
    public function it_can_execute_an_action(): void
    {
        $this->assertNull(TestAction::make()->execute('foo'));
    }

    #[Test]
    public function it_can_execute_an_action_with_return_data(): void
    {
        $this->assertEquals('bar', TestActionWithArgs::make()->execute(foo: 'bar'));
    }

    #[Test]
    public function it_can_execute_an_action_on_conditional(): void
    {
        $this->assertInstanceOf(Fluent::class, TestActionWithArgs::make()->executeIf(shouldExecute: false, foo: 'bar'));
        $this->assertEquals('bar', TestActionWithArgs::make()->executeIf(shouldExecute: true, foo: 'bar'));
    }

    #[Test]
    public function it_can_assert_execute_is_called(): void
    {
        TestAction::shouldExecute()
            ->once();

        (new TestClass)->doSomething();
    }

    #[Test]
    public function it_can_assert_execute_is_called_and_return_data(): void
    {
        TestActionWithArgs::shouldExecute()
            ->withArgs(['bar'])
            ->once()
            ->andReturn('bar');

        (new TestClass)->doSomethingWithArgs('bar');
    }

    #[Test]
    public function it_can_assert_execute_is_not_called(): void
    {
        TestAction::shouldExecute()
            ->never();

        (new TestClass)->doSomethingConditionally(shouldExecuteAction: false);
    }

    #[Test]
    public function it_can_assert_execute_is_called_fluently(): void
    {
        TestAction::shouldExecute()
            ->once();

        (new TestClass)->doSomethingConditionallyFluently(shouldExecuteAction: true);
    }
}
