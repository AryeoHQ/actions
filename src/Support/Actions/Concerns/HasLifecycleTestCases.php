<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Actions\Contracts\Action;
use Tests\Fixtures\Support\Orders\Actions\Archive;
use Tests\Fixtures\Support\Orders\Actions\WithFailed;
use Tests\Fixtures\Support\Orders\Actions\WithFailedAndMiddleware;
use Tests\Fixtures\Support\Orders\Actions\WithFailedAndSucceeded;
use Tests\Fixtures\Support\Orders\Actions\WithFailedThatThrows;
use Tests\Fixtures\Support\Orders\Actions\WithMiddlewareMethod;
use Tests\Fixtures\Support\Orders\Actions\WithMiddlewareProperty;
use Tests\Fixtures\Support\Orders\Actions\WithRelease;
use Tests\Fixtures\Support\Orders\Actions\WithSucceeded;
use Tests\Fixtures\Support\Orders\Actions\WithSucceededAndMiddleware;
use Tests\Fixtures\Support\Orders\Middleware\WritesToContext;
use Tests\Fixtures\Support\Orders\Middleware\WritesToContextBidirectional;
use Tests\Fixtures\Support\Orders\Order;

/** @mixin \Tests\TestCase */
trait HasLifecycleTestCases
{
    #[Test]
    public function it_executes_now_without_error_when_action_has_no_lifecycle_hooks(): void
    {
        $order = Order::factory()->make();

        $result = Archive::make($order)->now();

        $this->assertEquals($order->name.': archived', $result);
    }

    #[Test]
    public function it_calls_succeeded_after_now_completes(): void
    {
        $order = Order::factory()->make();

        WithSucceeded::make($order)->now();
        $this->assertContains(WithSucceeded::class, Context::get(Action::class));
    }

    #[Test]
    public function it_calls_succeeded_only_once_per_now(): void
    {
        $order = Order::factory()->make();

        tap(
            WithSucceeded::make($order),
            function (WithSucceeded $action) {
                $action->now();
                $action->now();
                $action->now();
            }
        );

        $context = Context::get(Action::class);

        $this->assertCount(3, array_filter($context, fn ($value) => $value === WithSucceeded::class));
    }

    #[Test]
    public function it_calls_failed_when_now_throws(): void
    {
        try {
            WithFailed::make()->now();
        } catch (RuntimeException) {
            // expected
        }

        $this->assertContains(WithFailed::class, Context::get(Action::class));
    }

    #[Test]
    public function it_rethrows_the_original_exception_after_calling_failed_when_now(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Action failed intentionally');

        WithFailed::make()->now();
    }

    #[Test]
    public function it_preserves_original_exception_when_failed_hook_throws_when_now(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Original exception');

        WithFailedThatThrows::make()->now();
    }

    #[Test]
    public function it_runs_middleware_from_actions_prepare_method_when_now(): void
    {
        $order = Order::factory()->make();

        WithMiddlewareProperty::make($order)->now();

        $this->assertContains(WritesToContext::class, Context::get(Action::class));
    }

    #[Test]
    public function it_runs_middleware_from_actions_middleware_method_when_now(): void
    {
        $order = Order::factory()->make();

        WithMiddlewareMethod::make($order)->now();

        $this->assertContains(WritesToContext::class, Context::get(Action::class));
    }

    #[Test]
    public function it_runs_lifecycle_in_correct_order_when_now(): void
    {
        WithSucceededAndMiddleware::make()->now();

        $this->assertSame([
            WritesToContextBidirectional::IN,
            WithSucceededAndMiddleware::HANDLE,
            WritesToContextBidirectional::OUT,
            WithSucceededAndMiddleware::SUCCEEDED,
        ], Context::get(Action::class));
    }

    #[Test]
    public function it_runs_failed_lifecycle_in_correct_order_when_now(): void
    {
        try {
            WithFailedAndMiddleware::make()->now();
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame([
            WritesToContextBidirectional::IN,
            WithFailedAndMiddleware::HANDLE,
            WithFailedAndMiddleware::FAILED,
        ], Context::get(Action::class));
    }

    #[Test]
    public function it_does_not_run_succeeded_when_now_faked(): void
    {
        WithSucceeded::fake();

        WithSucceeded::make(Order::factory()->make())->now();

        $this->assertEmpty(Context::get(Action::class, []));
    }

    #[Test]
    public function it_does_not_run_failed_when_now_faked(): void
    {
        WithFailed::fake();

        WithFailed::make()->now();

        $this->assertEmpty(Context::get(Action::class, []));
    }

    #[Test]
    public function it_does_not_call_prepare_when_now_faked(): void
    {
        WithMiddlewareProperty::fake();

        WithMiddlewareProperty::make(Order::factory()->make())->now();

        $this->assertEmpty(Context::get(Action::class, []));
    }

    #[Test]
    public function it_redispatches_on_sync_failure_when_now(): void
    {
        try {
            WithFailed::make()->dispatchAfterFailed()->now();
        } catch (RuntimeException) {
            // expected
        }

        $context = Context::get(Action::class, []);

        $this->assertCount(2, array_filter($context, fn ($value) => $value === WithFailed::HANDLE));
    }

    #[Test]
    public function it_still_calls_failed_when_dispatch_after_failed_when_now(): void
    {
        try {
            WithFailed::make()->dispatchAfterFailed()->now();
        } catch (RuntimeException) {
            // expected
        }

        $this->assertContains(WithFailed::class, Context::get(Action::class, []));
    }

    #[Test]
    public function it_rethrows_exception_when_dispatch_after_failed_when_now(): void
    {
        $this->expectException(RuntimeException::class);

        WithFailed::make()->dispatchAfterFailed()->now();
    }

    #[Test]
    public function it_redispatches_on_sync_success_when_now(): void
    {
        WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded()->now();

        $context = Context::get(Action::class, []);

        $this->assertCount(2, array_filter($context, fn ($value) => $value === WithSucceeded::HANDLE));
    }

    #[Test]
    public function it_still_calls_succeeded_when_dispatch_after_succeeded_when_now(): void
    {
        WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded()->now();

        $this->assertContains(WithSucceeded::class, Context::get(Action::class, []));
    }

    #[Test]
    public function it_runs_failed_hook_before_redispatch_on_sync_failure_when_now(): void
    {
        try {
            WithFailed::make()->dispatchAfterFailed()->now();
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(
            [
                WithFailed::HANDLE,
                WithFailed::class,
                WithFailed::HANDLE,
                WithFailed::class,
            ],
            Context::get(Action::class, [])
        );
    }

    #[Test]
    public function it_runs_succeeded_hook_before_redispatch_on_sync_success_when_now(): void
    {
        WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded()->now();

        $this->assertSame(
            [
                WithSucceeded::HANDLE,
                WithSucceeded::class,
                WithSucceeded::HANDLE,
                WithSucceeded::class,
            ],
            Context::get(Action::class, [])
        );
    }

    #[Test]
    public function it_does_not_redispatch_on_sync_success_when_released_when_now(): void
    {
        WithRelease::make()->dispatchAfterSucceeded()->now();

        $context = Context::get(Action::class, []);

        // A released run is not a success, so the success re-dispatch must not fire.
        $this->assertCount(
            1,
            array_filter($context, fn ($value) => $value === WithRelease::HANDLE)
        );
    }

    #[Test]
    public function it_dispatches_without_error_when_action_has_no_lifecycle_hooks(): void
    {
        $order = Order::factory()->make();

        Archive::make($order)->dispatch();

        $this->assertNull(Context::get(Action::class));
    }

    #[Test]
    public function it_calls_succeeded_after_dispatch_completes(): void
    {
        $order = Order::factory()->make();

        WithSucceeded::make($order)->dispatch();

        $this->assertContains(WithSucceeded::class, Context::get(Action::class));
    }

    #[Test]
    public function it_calls_succeeded_only_once_per_dispatch(): void
    {
        $order = Order::factory()->make();

        tap(
            WithSucceeded::make($order),
            function (WithSucceeded $action) {
                $action->dispatch();
                $action->dispatch();
                $action->dispatch();
            }
        );

        $context = Context::get(Action::class);

        $this->assertCount(3, array_filter($context, fn ($value) => $value === WithSucceeded::class));
    }

    #[Test]
    public function it_calls_failed_when_dispatch_throws(): void
    {
        try {
            WithFailedAndSucceeded::make()->dispatch();
        } catch (RuntimeException) {
            // expected
        }

        $this->assertContains(WithFailedAndSucceeded::FAILED, Context::get(Action::class, []));
    }

    #[Test]
    public function it_does_not_call_succeeded_when_dispatch_throws(): void
    {
        try {
            WithFailedAndSucceeded::make()->dispatch();
        } catch (RuntimeException) {
            // expected
        }

        $this->assertNotContains(WithFailedAndSucceeded::SUCCEEDED, Context::get(Action::class, []));
    }

    #[Test]
    public function it_runs_middleware_from_actions_prepare_method_when_dispatch(): void
    {
        $order = Order::factory()->make();

        WithMiddlewareProperty::make($order)->dispatch();

        $this->assertContains(WritesToContext::class, Context::get(Action::class));
    }

    #[Test]
    public function it_runs_middleware_from_actions_middleware_method_when_dispatch(): void
    {
        $order = Order::factory()->make();

        WithMiddlewareMethod::make($order)->dispatch();

        $this->assertContains(WritesToContext::class, Context::get(Action::class));
    }

    #[Test]
    public function it_runs_lifecycle_in_correct_order_when_dispatch(): void
    {
        WithSucceededAndMiddleware::make()->dispatch();

        $this->assertSame([
            WritesToContextBidirectional::IN,
            WithSucceededAndMiddleware::HANDLE,
            WritesToContextBidirectional::OUT,
            WithSucceededAndMiddleware::SUCCEEDED,
        ], Context::get(Action::class));
    }

    #[Test]
    public function it_runs_failed_lifecycle_in_correct_order_when_dispatch(): void
    {
        try {
            WithFailedAndMiddleware::make()->dispatch();
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame([
            WritesToContextBidirectional::IN,
            WithFailedAndMiddleware::HANDLE,
            WithFailedAndMiddleware::FAILED,
        ], Context::get(Action::class));
    }

    #[Test]
    public function it_does_not_run_succeeded_when_dispatch_faked(): void
    {
        WithSucceeded::fake();

        WithSucceeded::make(Order::factory()->make())->dispatch();

        $this->assertEmpty(Context::get(Action::class, []));
    }

    #[Test]
    public function it_does_not_run_failed_when_dispatch_faked(): void
    {
        WithFailed::fake();

        WithFailed::make()->dispatch();

        $this->assertEmpty(Context::get(Action::class, []));
    }

    #[Test]
    public function it_does_not_call_prepare_when_dispatch_faked(): void
    {
        WithMiddlewareProperty::fake();

        WithMiddlewareProperty::make(Order::factory()->make())->dispatch();

        $this->assertEmpty(Context::get(Action::class, []));
    }

    #[Test]
    public function it_calls_succeeded_after_dispatch_sync_completes(): void
    {
        $order = Order::factory()->make();

        dispatch_sync(WithSucceeded::make($order));

        $this->assertContains(WithSucceeded::class, Context::get(Action::class));
    }

    #[Test]
    public function it_calls_failed_when_dispatch_sync_throws(): void
    {
        try {
            dispatch_sync(WithFailedAndSucceeded::make());
        } catch (RuntimeException) {
            // expected
        }

        $this->assertContains(WithFailedAndSucceeded::FAILED, Context::get(Action::class, []));
        $this->assertNotContains(WithFailedAndSucceeded::SUCCEEDED, Context::get(Action::class, []));
    }

    #[Test]
    public function it_redispatches_on_sync_failure_when_dispatch_sync(): void
    {
        try {
            dispatch_sync(WithFailed::make()->dispatchAfterFailed());
        } catch (RuntimeException) {
            // expected
        }

        $context = Context::get(Action::class, []);

        $this->assertCount(2, array_filter($context, fn ($value) => $value === WithFailed::HANDLE));
        $this->assertContains(WithFailed::class, $context);
    }

    #[Test]
    public function it_redispatches_on_sync_success_when_dispatch_sync(): void
    {
        dispatch_sync(WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded());

        $context = Context::get(Action::class, []);

        $this->assertCount(2, array_filter($context, fn ($value) => $value === WithSucceeded::HANDLE));
        $this->assertContains(WithSucceeded::class, $context);
    }

    #[Test]
    public function it_runs_failed_hook_before_redispatch_on_sync_failure_when_dispatch_sync(): void
    {
        try {
            dispatch_sync(WithFailed::make()->dispatchAfterFailed());
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(
            [
                WithFailed::HANDLE,
                WithFailed::class,
                WithFailed::HANDLE,
                WithFailed::class,
            ],
            Context::get(Action::class, [])
        );
    }

    #[Test]
    public function it_runs_succeeded_hook_before_redispatch_on_sync_success_when_dispatch_sync(): void
    {
        dispatch_sync(WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded());

        $this->assertSame(
            [
                WithSucceeded::HANDLE,
                WithSucceeded::class,
                WithSucceeded::HANDLE,
                WithSucceeded::class,
            ],
            Context::get(Action::class, [])
        );
    }
}
