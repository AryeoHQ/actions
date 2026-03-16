<?php

namespace Tests;

use Orchestra\Testbench;
use Support\Actions\Providers\Provider;

abstract class TestCase extends Testbench\TestCase
{
    /** @var \Illuminate\Testing\TestResponse|null */
    public static $latestResponse = null;

    protected function defineEnvironment($app): void
    {
        config()->set('queue.default', 'sync');
    }

    protected function getPackageProviders($app): array
    {
        return [
            Provider::class,
        ];
    }
}
