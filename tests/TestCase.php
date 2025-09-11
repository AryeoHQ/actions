<?php

namespace Tests;

use Orchestra\Testbench;
use Support\Actions\Providers\ActionServiceProvider;

abstract class TestCase extends Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ActionServiceProvider::class,
        ];
    }
}
