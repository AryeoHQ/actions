<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

use Illuminate\Bus\UniqueLock;
use Illuminate\Queue\Middleware\WithoutOverlapping as WithoutOverlappingMiddleware;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use LogicException;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Actions\Contracts\Action;
use Support\Actions\Pipeline\Exceptions\Interrupted;
use Tests\Fixtures\Support\Orders\Actions\Unique;
use Tests\Fixtures\Support\Orders\Actions\UniqueThatCallsItself;
use Tests\Fixtures\Support\Orders\Actions\UniqueThatFails;
use Tests\Fixtures\Support\Orders\Actions\UniqueThatFailsToClone;
use Tests\Fixtures\Support\Orders\Actions\UniqueThatRecordsItsLockState;
use Tests\Fixtures\Support\Orders\Actions\UniqueUntilProcessingThatRecordsItsLockState;
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
    public function it_passes_non_action_commands_through_dispatch(): void
    {
        Bus::fake();

        $job = new NonActionQueueable;

        dispatch($job);

        Bus::assertDispatched(NonActionQueueable::class);
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
            // The run stopped before handle(). So the context is empty: no handle, no hook,
            // and no re-dispatched copy.
            $this->assertEmpty(Context::get(Action::class, []));
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function it_runs_now_when_the_unique_lock_is_free(): void
    {
        Unique::make()->now();

        $this->assertContains(Unique::HANDLE, Context::get(Action::class));
    }

    #[Test]
    public function it_releases_the_unique_lock_after_a_now_run(): void
    {
        $action = Unique::make();

        $action->now();
        $action->now();

        $this->assertSame([Unique::HANDLE, Unique::HANDLE], Context::get(Action::class));
    }

    #[Test]
    public function it_prevents_a_now_run_while_the_same_unique_action_is_running(): void
    {
        UniqueThatCallsItself::make()->now();

        $this->assertSame(
            [UniqueThatCallsItself::HANDLE, UniqueThatCallsItself::PREVENTED],
            Context::get(Action::class)
        );
    }

    #[Test]
    public function it_releases_the_unique_lock_before_re_dispatching_after_a_failed_now_run(): void
    {
        Bus::fake();

        // handle() fails, then a copy is re-dispatched. The copy needs the same unique lock, so the
        // run must free the lock first. If the run still held it, the copy would not dispatch.
        try {
            UniqueThatFails::make()->dispatchAfterFailed()->now();

            $this->fail('Expected the handle() failure to surface.');
        } catch (RuntimeException) {
            UniqueThatFails::assertFired();
        }
    }

    #[Test]
    public function it_releases_the_unique_lock_when_the_snapshot_throws(): void
    {
        // The snapshot is built before the lifecycle runs. If it throws, the lifecycle release never
        // fires, so the lock must be freed here — otherwise it leaks with no expiry.
        $run = fn () => UniqueThatFailsToClone::make()->now();

        $this->assertThrows($run, LogicException::class);

        // The second run reaches the clone again (LogicException). A leaked lock would throw Prevented.
        $this->assertThrows($run, LogicException::class);
    }

    #[Test]
    public function it_holds_the_unique_lock_through_handle_for_a_plain_unique_action(): void
    {
        UniqueThatRecordsItsLockState::make()->now();

        // Plain unique holds the lock during handle(). So the probe inside handle() cannot take it.
        $this->assertContains(UniqueThatRecordsItsLockState::LOCK_HELD, Context::get(Action::class));
    }

    #[Test]
    public function it_releases_the_unique_lock_before_handle_for_an_until_processing_action(): void
    {
        UniqueUntilProcessingThatRecordsItsLockState::make()->now();

        // Until-processing frees the lock before handle(). So the probe inside handle() can take it.
        $this->assertContains(UniqueUntilProcessingThatRecordsItsLockState::LOCK_FREE, Context::get(Action::class));
    }

    #[Test]
    public function it_does_not_double_release_and_free_a_concurrent_lock_for_an_until_processing_action(): void
    {
        UniqueUntilProcessingThatRecordsItsLockState::make()->now();

        // The probe took the lock inside handle(). It stands for a second run that holds the lock.
        // The first run must not free that lock. So the key must still be locked now.
        $this->assertFalse(
            (new UniqueLock(Cache::store()))->acquire(UniqueUntilProcessingThatRecordsItsLockState::make()),
            'the concurrent lock was clobbered by a double release'
        );

        (new UniqueLock(Cache::store()))->release(UniqueUntilProcessingThatRecordsItsLockState::make());
    }
}
