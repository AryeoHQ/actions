<?php

namespace Tests\Fixtures;

class TestClass
{
    public function doSomething(): void
    {
        TestAction::make()->execute();
    }

    public function doSomethingConditionally(bool $shouldExecuteAction = true): void
    {
        if ($shouldExecuteAction) {
            TestAction::make()->execute($shouldExecuteAction);
        }
    }

    public function doSomethingConditionallyFluently(bool $shouldExecuteAction = true): void
    {
        TestAction::make()->executeIf($shouldExecuteAction);
    }
}