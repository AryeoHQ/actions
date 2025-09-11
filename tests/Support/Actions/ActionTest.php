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
    public function itCanExecuteAnAction(): void
    {
        $this->assertNull(TestAction::make()->execute('foo'));
    }

    #[Test]
    public function itCanExecuteAnActionWithReturnData(): void
    {
        $this->assertEquals('bar', TestActionWithArgs::make()->execute(foo: 'bar'));
    }

    #[Test]
    public function itCanExecuteAnActionOnConditional(): void
    {
        $this->assertInstanceOf(Fluent::class, TestActionWithArgs::make()->executeIf(shouldExecute: false, foo: 'bar'));
        $this->assertEquals('bar', TestActionWithArgs::make()->executeIf(shouldExecute: true, foo: 'bar'));
    }

    #[Test]
    public function itCanAssertExecuteIsCalled(): void
    {
        TestAction::shouldExecute()
            ->once();

        (new TestClass)->doSomething();
    }

    #[Test]
    public function itCanAssertExecuteIsCalledAndReturnData(): void
    {
        TestActionWithArgs::shouldExecute()
            ->withArgs(['bar'])
            ->once()
            ->andReturn('bar');

        (new TestClass)->doSomethingWithArgs('bar');
    }

    #[Test]
    public function itCanAssertExecuteIsNotCalled(): void
    {
        TestAction::shouldExecute()
            ->never();

        (new TestClass)->doSomethingConditionally(shouldExecuteAction: false);
    }

    #[Test]
    public function itCanAssertExecuteIsCalledFluently(): void
    {
        TestAction::shouldExecute()
            ->once();

        (new TestClass)->doSomethingConditionallyFluently(shouldExecuteAction: true);
    }
}