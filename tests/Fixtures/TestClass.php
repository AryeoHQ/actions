<?php

namespace Tests\Fixtures;

class TestClass
{
    public function doSomething(...$arguments): void
    {
        TestAction::make()->execute(...$arguments);
    }

    public function doSomethingWithArgs(...$arguments): void
    {
        TestActionWithArgs::make()->execute(...$arguments);
    }

    public function doSomethingConditionally(bool $shouldExecuteAction = true): void
    {
        if ($shouldExecuteAction) {
            TestAction::make()->execute();
        }
    }

    public function doSomethingConditionallyFluently(bool $shouldExecuteAction = true): void
    {
        TestAction::make()->executeIf($shouldExecuteAction);
    }
}
