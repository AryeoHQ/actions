<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\ManuallyFailedException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Actions\Contracts\Action;
use Tests\Fixtures\Support\Orders\Actions\Archive;
use Tests\Fixtures\Support\Orders\Actions\WithFailOnException;
use Tests\Fixtures\Support\Orders\Actions\WithManualFail;
use Tests\Fixtures\Support\Orders\Actions\WithRelease;
use Tests\Fixtures\Support\Orders\Actions\WithSucceeded;
use Tests\Fixtures\Support\Orders\Middleware\WritesToContext;
use Tests\Fixtures\Support\Orders\Order;

trait DispatchableTestCases
{
    #[Test]
    public function it_returns_pending_dispatch_when_dispatched(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        $pendingDispatch = Archive::make($order)->dispatch();

        $this->assertInstanceOf(PendingDispatch::class, $pendingDispatch);
    }

    #[Test]
    public function it_can_be_dispatched_to_the_queue(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatch();

        Archive::assertFired();
    }

    #[Test]
    public function you_can_confirm_it_is_dispatched_to_the_queue_using_a_callback(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatch();

        Archive::assertFired(
            fn (Archive $action) => $action->order->name === $order->name
        );
    }

    #[Test]
    public function you_can_confirm_it_was_not_dispatched_to_the_queue_using_a_callback(): void
    {
        Archive::fake();
        $orders = Order::factory()->times(2)->make();

        Archive::make($orders->first())->dispatch();

        Archive::assertNotFired(
            fn (Archive $action) => $action->order->name === $orders->last()->name
        );
    }

    #[Test]
    public function it_can_be_conditionally_dispatched_to_the_queue(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatchIf(true);

        Archive::assertFired();
    }

    #[Test]
    public function it_does_not_dispatch_to_the_queue_conditionally(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatchIf(false);

        Archive::assertNotFired();
    }

    #[Test]
    public function it_can_be_dispatched_to_the_queue_unless(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatchUnless(false);

        Archive::assertFired();
    }

    #[Test]
    public function it_does_not_dispatch_to_the_queue_unless(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatchUnless(true);

        Archive::assertNotFired();
    }

    #[Test]
    public function you_can_confirm_dispatched_times_to_the_queue(): void
    {
        Archive::fake();
        $orders = Order::factory()->times(2)->make();

        $orders->each(fn (Order $order) => Archive::make($order)->dispatch());

        Archive::assertFiredTimes($orders->count());
    }

    #[Test]
    public function it_supports_through_while_preserving_required_middleware(): void
    {
        $order = Order::factory()->make();

        WithSucceeded::make($order)->dispatch()->through([]);

        $this->assertContains(WithSucceeded::class, Context::get(Action::class));
    }

    #[Test]
    public function it_runs_middleware_passed_to_through(): void
    {
        $order = Order::factory()->make();

        WithSucceeded::make($order)->dispatch()->through([WritesToContext::class]);

        $this->assertContains(WritesToContext::class, Context::get(Action::class));
    }

    #[Test]
    public function it_runs_consumer_middleware_before_succeeded(): void
    {
        $order = Order::factory()->make();

        WithSucceeded::make($order)->dispatch()->through([WritesToContext::class]);

        $this->assertSame([WritesToContext::class, WithSucceeded::class], Context::get(Action::class));
    }

    #[Test]
    public function it_throws_manually_failed_exception_when_bare_fail_is_called_when_now(): void
    {
        $this->expectException(ManuallyFailedException::class);

        WithManualFail::make()->now();
    }

    #[Test]
    public function it_throws_manually_failed_exception_from_string_when_fail_is_called_when_now(): void
    {
        $this->expectException(ManuallyFailedException::class);
        $this->expectExceptionMessage('failure');

        WithManualFail::make('failure')->now();
    }

    #[Test]
    public function it_throws_the_given_exception_when_fail_is_called_when_now(): void
    {
        $this->expectException(RuntimeException::class);

        WithManualFail::make(new RuntimeException)->now();
    }

    #[Test]
    public function it_calls_failed_exactly_once_when_fail_is_called_when_now(): void
    {
        try {
            WithManualFail::make()->now();
        } catch (ManuallyFailedException) {
            // expected
        }

        $context = Context::get(Action::class, []);

        $this->assertCount(1, array_filter($context, fn ($value) => $value === WithManualFail::FAILED));
    }

    #[Test]
    public function it_converts_a_string_to_the_exception_given_to_failed_when_fail_is_called_when_now(): void
    {
        try {
            WithManualFail::make('failure')->now();
        } catch (ManuallyFailedException) {
            // expected
        }

        $exception = Context::get(WithManualFail::FAILED);

        $this->assertInstanceOf(ManuallyFailedException::class, $exception);
        $this->assertSame('failure', $exception->getMessage());
    }

    #[Test]
    public function it_does_not_run_code_after_fail_when_now(): void
    {
        try {
            WithManualFail::make()->now();
        } catch (ManuallyFailedException) {
            // expected
        }

        $this->assertNotContains(WithManualFail::AFTER_FAIL, Context::get(Action::class, []));
    }

    #[Test]
    public function it_does_not_call_succeeded_when_fail_is_called_when_now(): void
    {
        try {
            WithManualFail::make()->now();
        } catch (ManuallyFailedException) {
            // expected
        }

        $this->assertNotContains(WithManualFail::SUCCEEDED, Context::get(Action::class, []));
    }

    #[Test]
    public function it_marks_the_job_as_failed_and_continues_executing_when_fail_is_called_in_the_queue(): void
    {
        $action = WithManualFail::make()->withFakeQueueInteractions();

        $result = $action->now();

        $action->assertFailed();
        $action->assertFailedWith(ManuallyFailedException::class);

        $this->assertSame('completed', $result);
        $this->assertContains(WithManualFail::AFTER_FAIL, Context::get(Action::class, []));
    }

    #[Test]
    public function it_rethrows_the_original_exception_when_fail_on_exception_middleware_fails_the_action_when_now(): void
    {
        $this->expectException(AuthorizationException::class);

        WithFailOnException::make()->now();
    }

    #[Test]
    public function it_calls_failed_exactly_once_when_fail_on_exception_middleware_fails_the_action_when_now(): void
    {
        try {
            WithFailOnException::make()->now();
        } catch (AuthorizationException) {
            // expected
        }

        $context = Context::get(Action::class, []);

        $this->assertCount(1, array_filter($context, fn ($value) => $value === WithFailOnException::FAILED));
    }

    #[Test]
    public function it_calls_failed_exactly_once_when_fail_is_called_via_sync_queue_driver(): void
    {
        try {
            WithManualFail::make()->dispatch();
        } catch (ManuallyFailedException) {
            // expected — the sync driver rethrows after Job::fail()
        }

        $context = Context::get(Action::class, []);

        $this->assertCount(1, array_filter($context, fn ($value) => $value === WithManualFail::FAILED));
    }

    #[Test]
    public function it_fires_the_job_failed_event_when_fail_is_called_via_sync_queue_driver(): void
    {
        Event::fake([JobFailed::class]);

        try {
            WithManualFail::make()->dispatch();
        } catch (ManuallyFailedException) {
            // expected
        }

        Event::assertDispatched(JobFailed::class);
    }

    #[Test]
    public function it_propagates_the_exception_to_the_dispatch_caller_when_fail_is_called_via_sync_queue_driver(): void
    {
        $this->expectException(ManuallyFailedException::class);

        WithManualFail::make()->dispatch();
    }

    #[Test]
    public function it_propagates_the_exception_to_the_caller_when_fail_is_called_when_dispatch_sync(): void
    {
        $this->expectException(ManuallyFailedException::class);

        dispatch_sync(WithManualFail::make());
    }

    #[Test]
    public function it_does_not_call_succeeded_when_fail_is_called_via_sync_queue_driver(): void
    {
        try {
            WithManualFail::make()->dispatch();
        } catch (ManuallyFailedException) {
            // expected
        }

        $this->assertNotContains(WithManualFail::SUCCEEDED, Context::get(Action::class, []));
    }

    #[Test]
    public function it_does_not_call_succeeded_when_the_job_is_released(): void
    {
        WithRelease::make()->dispatch();

        $context = Context::get(Action::class, []);

        $this->assertContains(WithRelease::HANDLE, $context);
        $this->assertNotContains(WithRelease::SUCCEEDED, $context);
    }
}
