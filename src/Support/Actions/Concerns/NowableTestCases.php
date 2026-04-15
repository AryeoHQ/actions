<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Fixtures\Support\Orders\Actions\Archive;
use Tests\Fixtures\Support\Orders\Actions\Notify;
use Tests\Fixtures\Support\Orders\Actions\Ship;
use Tests\Fixtures\Support\Orders\Actions\WithFailed;
use Tests\Fixtures\Support\Orders\Actions\WithFailedThatThrows;
use Tests\Fixtures\Support\Orders\Actions\WithSucceeded;
use Tests\Fixtures\Support\Orders\Order;

trait NowableTestCases
{
    #[Test]
    public function it_can_be_executed_now(): void
    {
        $order = Order::factory()->make();

        $result = Archive::make($order)->now();

        $this->assertEquals($order->name.': archived', $result);
    }

    #[Test]
    public function you_can_confirm_executed_now(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn('fake-value');

        Archive::make($order)->now();

        Archive::assertFired();
    }

    #[Test]
    public function it_can_be_faked_when_executed_now(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn($expected = 'test');

        $result = Archive::make($order)->now();

        Archive::assertFired();
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_can_be_faked_multiple_times_when_executed_now(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn('first');
        $result1 = Archive::make($order)->now();

        Archive::fake()->andReturn('second');
        $result2 = Archive::make($order)->now();

        $this->assertEquals('first', $result1);
        $this->assertEquals('second', $result2);
        Archive::assertFiredTimes(2);
    }

    #[Test]
    public function it_can_be_conditionally_executed_now(): void
    {
        $order = Order::factory()->make();
        $result = Archive::make($order)->nowIf(true);

        $this->assertEquals($order->name.': archived', $result);
    }

    #[Test]
    public function it_can_be_faked_when_conditionally_executed_now(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn($expected = 'test');

        $result = Archive::make($order)->nowIf(true);

        $this->assertEquals($expected, $result);
        Archive::assertFired();
    }

    #[Test]
    public function it_does_not_execute_now_conditionally(): void
    {
        $order = Order::factory()->make();
        $result = Archive::make($order)->nowIf(false);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_be_executed_now_unless(): void
    {
        $order = Order::factory()->make();
        $result = Archive::make($order)->nowUnless(false);

        $this->assertEquals($order->name.': archived', $result);
    }

    #[Test]
    public function it_can_be_faked_when_executed_now_unless(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn($expected = 'test');

        $result = Archive::make($order)->nowUnless(false);

        Archive::assertFired();
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_does_not_execute_now_unless(): void
    {
        $order = Order::factory()->make();
        $result = Archive::make($order)->nowUnless(true);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_use_dependency_injection(): void
    {
        $order = Order::factory()->make();
        $result = Ship::make($order)->now();

        $this->assertEquals($order->name.': shipped and notified', $result);
    }

    #[Test]
    public function it_can_be_faked_with_dependency_injection(): void
    {
        $order = Order::factory()->make();
        Ship::fake()->andReturn($expected = 'test');

        $result = Ship::make($order)->now();

        Ship::assertFired();
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_can_fake_nested_actions(): void
    {
        $order = Order::factory()->make();
        Notify::fake()->andReturn('test');

        $result = Ship::make($order)->now();

        $this->assertEquals($order->name.': shipped and test', $result);
    }

    #[Test]
    public function it_can_fake_actions_and_nested_actions(): void
    {
        $order = Order::factory()->make();
        Notify::fake()->andReturn('test');
        Ship::fake()->andReturn('mocked-ship');

        $result = Ship::make($order)->now();

        $this->assertEquals('mocked-ship', $result);
    }

    #[Test]
    public function it_can_assert_now_was_called_with_callback(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn('fake-value');

        Archive::make($order)->now();

        Archive::assertFired(
            fn ($action) => $action->order->name === $order->name
        );
    }

    #[Test]
    public function it_can_assert_now_times(): void
    {
        $orders = Order::factory()->times(2)->make();
        Archive::fake()->andReturn('fake-value');

        $orders->each(
            fn (Order $order) => Archive::make($order)->now()
        );

        Archive::assertFiredTimes(2);
    }

    #[Test]
    public function it_can_assert_dispatched_using_action_method(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn('fake-value');

        Archive::make($order)->now();

        Archive::assertFired();
    }

    #[Test]
    public function it_can_assert_not_fired_using_action_method(): void
    {
        Archive::fake()->andReturn('fake-value');

        Archive::assertNotFired();
    }

    #[Test]
    public function it_can_use_closure_with_job_parameter_in_and_return(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn(fn ($job) => $job->order->name.': dynamic result');

        $result = Archive::make($order)->now();

        $this->assertEquals($order->name.': dynamic result', $result);
    }

    #[Test]
    public function it_can_assert_not_now_was_called(): void
    {
        Archive::fake()->andReturn('fake-value');

        // Don't execute the action with now()

        Archive::assertNotFired();
    }

    #[Test]
    public function it_returns_null_when_fake_without_and_return(): void
    {
        $order = Order::factory()->make();
        Archive::fake();

        $result = Archive::make($order)->now();

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_chain_and_return_multiple_times(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn('first')->andReturn('second');

        $result = Archive::make($order)->now();

        $this->assertEquals('second', $result);
    }

    #[Test]
    public function it_can_assert_now_with_times_parameter(): void
    {
        $orders = Order::factory()->times(2)->make();
        Archive::fake()->andReturn('fake-value');

        $orders->each(
            fn (Order $order) => Archive::make($order)->now()
        );

        Archive::assertFired(2);
    }

    #[Test]
    public function it_throws_exception_from_closure_in_and_return(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn(fn () => throw new RuntimeException('Test exception'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test exception');

        Archive::make($order)->now();
    }

    #[Test]
    public function it_can_fake_action_twice_updating_return_value(): void
    {
        $order = Order::factory()->make();
        Archive::fake()->andReturn('first-fake');

        $result1 = Archive::make($order)->now();

        Archive::fake()->andReturn('second-fake');

        $result2 = Archive::make($order)->now();

        $this->assertEquals('first-fake', $result1);
        $this->assertEquals('second-fake', $result2);
    }

    #[Test]
    public function it_calls_succeeded_after_now_completes(): void
    {
        $order = Order::factory()->make();

        $result = WithSucceeded::make($order)->now();

        $this->assertEquals($order->name.': archived', $result);
        $this->assertContains(WithSucceeded::class, Context::get('execution_log'));
    }

    #[Test]
    public function it_calls_failed_when_now_throws(): void
    {
        try {
            WithFailed::make()->now();
        } catch (RuntimeException) {
            // expected
        }

        $this->assertContains(WithFailed::class, Context::get('execution_log'));
    }

    #[Test]
    public function it_rethrows_the_original_exception_after_calling_failed(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Action failed intentionally');

        WithFailed::make()->now();
    }

    #[Test]
    public function it_preserves_original_exception_when_failed_hook_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Original exception');

        WithFailedThatThrows::make()->now();
    }

    #[Test]
    public function it_executes_now_without_error_when_action_has_no_lifecycle_hooks(): void
    {
        $order = Order::factory()->make();

        $result = Archive::make($order)->now();

        $this->assertEquals($order->name.': archived', $result);
    }
}
