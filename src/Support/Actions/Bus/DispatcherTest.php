<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

use Illuminate\Queue\Middleware\WithoutOverlapping as WithoutOverlappingMiddleware;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Bus\Pipelines\Exceptions\Interrupted;
use Support\Actions\Contracts\Action;
use Support\Actions\Middleware\Lifecycle\RunSucceeded;
use Tests\Fixtures\Support\Orders\Actions\WithoutOverlapping;
use Tests\Fixtures\Support\Orders\Actions\WithoutOverlappingAndLifecycleHooks;
use Tests\Fixtures\Support\Orders\NonAction;
use Tests\Fixtures\Support\Orders\NonActionQueueable;
use Tests\TestCase;

#[CoversClass(Dispatcher::class)]
#[WithConfig('cache.default', 'array')]
class DispatcherTest extends TestCase
{
    #[Test]
    public function it_passes_non_action_commands_through_dispatch_without_lifecycle_middleware(): void
    {
        Bus::fake();

        $job = new NonActionQueueable;

        dispatch($job);

        $this->assertNotContains(RunSucceeded::class, $job->middleware);
    }

    #[Test]
    public function it_passes_non_action_commands_through_dispatch_now_without_lifecycle_middleware(): void
    {
        $job = new NonActionQueueable;

        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchNow($job);

        $this->assertSame([NonActionQueueable::class], Context::get(Action::class));
    }

    #[Test]
    public function it_delegates_dispatch_sync(): void
    {
        $job = new NonAction;

        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchSync($job);

        $this->assertSame([NonAction::class], Context::get(Action::class));
    }

    #[Test]
    public function it_forwards_unknown_methods_to_decorated_dispatcher(): void
    {
        $dispatcher = $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class);

        $this->assertInstanceOf(Dispatcher::class, $dispatcher);

        $this->assertFalse($dispatcher->hasCommandHandler(new NonActionQueueable));
    }

    #[Test]
    public function it_does_not_call_prepare_for_non_actions(): void
    {
        $job = new NonActionQueueable;

        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchNow($job);

        $this->assertSame([NonActionQueueable::class], Context::get(Action::class));
    }

    #[Test]
    public function it_runs_now_when_no_middleware_blocks(): void
    {
        WithoutOverlapping::make()->now();

        $this->assertContains(WithoutOverlapping::HANDLE, Context::get(Action::class));
    }

    #[Test]
    public function it_throws_when_a_now_run_is_interrupted_by_middleware(): void
    {
        $lock = Cache::lock(
            (new WithoutOverlappingMiddleware(WithoutOverlapping::KEY))->getLockKey(WithoutOverlapping::make())
        );

        $this->assertTrue($lock->get());

        try {
            $this->expectException(Interrupted::class);

            WithoutOverlapping::make()->now();
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function it_does_not_run_lifecycle_hooks_when_a_now_run_is_interrupted(): void
    {
        $lock = Cache::lock(
            (new WithoutOverlappingMiddleware(WithoutOverlappingAndLifecycleHooks::KEY))
                ->getLockKey(WithoutOverlappingAndLifecycleHooks::make())
        );

        $this->assertTrue($lock->get());

        try {
            WithoutOverlappingAndLifecycleHooks::make()->now();

            $this->fail('Expected '.Interrupted::class.' to be thrown.');
        } catch (Interrupted) {
            $context = Context::get(Action::class, []);

            $this->assertNotContains(WithoutOverlappingAndLifecycleHooks::HANDLE, $context);
            $this->assertNotContains(WithoutOverlappingAndLifecycleHooks::SUCCEEDED, $context);
            $this->assertNotContains(WithoutOverlappingAndLifecycleHooks::FAILED, $context);
        } finally {
            $lock->release();
        }
    }
}
