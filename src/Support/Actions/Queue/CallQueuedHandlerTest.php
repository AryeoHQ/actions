<?php

declare(strict_types=1);

namespace Support\Actions\Queue;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Queue;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Actions\Contracts\Action;
use Tests\Fixtures\Support\Actions\Queue\QueuedJob;
use Tests\Fixtures\Support\Orders\Actions\WithFailed;
use Tests\Fixtures\Support\Orders\Actions\WithFailedThatThrows;
use Tests\Fixtures\Support\Orders\Actions\WithHooksRecordingJobState;
use Tests\Fixtures\Support\Orders\Actions\WithSucceeded;
use Tests\Fixtures\Support\Orders\Order;
use Tests\TestCase;

#[CoversClass(CallQueuedHandler::class)]
class CallQueuedHandlerTest extends TestCase
{
    #[Test]
    public function it_redispatches_after_success_for_a_queued_action(): void
    {
        Queue::fake();

        $action = WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded()->initialize();

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->call(
            new QueuedJob,
            $this->payloadFor($action),
        );

        Queue::assertPushed(WithSucceeded::class);
    }

    #[Test]
    public function it_redispatches_after_failure_for_a_queued_action(): void
    {
        Queue::fake();

        $action = WithFailed::make()->dispatchAfterFailed()->initialize();

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->failed(
            $this->payloadFor($action),
            new RuntimeException,
            'uuid',
            new QueuedJob,
        );

        Queue::assertPushed(WithFailed::class);
    }

    #[Test]
    public function it_redispatches_a_standalone_copy_without_the_chain(): void
    {
        Queue::fake();

        $action = WithSucceeded::make(Order::factory()->make())
            ->dispatchAfterSucceeded()
            ->initialize()
            ->chain([WithSucceeded::make(Order::factory()->make())]);

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->call(
            new QueuedJob,
            $this->payloadFor($action),
        );

        Queue::assertPushed(
            WithSucceeded::class,
            fn (WithSucceeded $pushed): bool => $pushed->chained === []
        );
    }

    #[Test]
    public function it_does_not_redispatch_the_copy_again(): void
    {
        Queue::fake();

        $action = WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded()->initialize();

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->call(
            new QueuedJob,
            $this->payloadFor($action),
        );

        Queue::assertPushed(
            WithSucceeded::class,
            fn (WithSucceeded $pushed): bool => $pushed->dispatchesAfterSucceeded === false
        );
    }

    #[Test]
    public function it_still_redispatches_when_the_queued_failed_hook_throws(): void
    {
        Queue::fake();

        $action = WithFailedThatThrows::make()->dispatchAfterFailed()->initialize();

        try {
            resolve(\Illuminate\Queue\CallQueuedHandler::class)->failed(
                $this->payloadFor($action),
                new RuntimeException,
                'uuid',
                new QueuedJob,
            );
            $this->fail('Expected the throwing failed() hook to propagate.');
        } catch (LogicException) {
            // expected — the hook exception still surfaces to the worker
        }

        Queue::assertPushed(WithFailedThatThrows::class);
    }

    #[Test]
    public function it_does_not_redispatch_a_released_job(): void
    {
        Queue::fake();

        $action = WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded()->initialize();

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->call(
            new QueuedJob(released: true),
            $this->payloadFor($action),
        );

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_does_not_redispatch_a_failed_job_on_the_success_path(): void
    {
        Queue::fake();

        $action = WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded()->initialize();

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->call(
            new QueuedJob(failed: true),
            $this->payloadFor($action),
        );

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_runs_succeeded_before_redispatch_for_a_queued_action(): void
    {
        Queue::fake();

        $action = WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded()->initialize();

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->call(
            new QueuedJob,
            $this->payloadFor($action),
        );

        $this->assertContains(WithSucceeded::class, Context::get(Action::class, []));
        Queue::assertPushed(WithSucceeded::class);
    }

    #[Test]
    public function it_does_not_run_succeeded_for_a_released_job(): void
    {
        Queue::fake();

        $action = WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded()->initialize();

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->call(
            new QueuedJob(released: true),
            $this->payloadFor($action),
        );

        $this->assertNotContains(WithSucceeded::class, Context::get(Action::class, []));
    }

    #[Test]
    public function it_does_not_run_succeeded_for_a_failed_job(): void
    {
        Queue::fake();

        $action = WithSucceeded::make(Order::factory()->make())->dispatchAfterSucceeded()->initialize();

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->call(
            new QueuedJob(failed: true),
            $this->payloadFor($action),
        );

        $this->assertNotContains(WithSucceeded::class, Context::get(Action::class, []));
    }

    #[Test]
    public function it_does_not_redispatch_when_no_flag_is_set(): void
    {
        Queue::fake();

        $action = WithSucceeded::make(Order::factory()->make())->initialize();

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->call(
            new QueuedJob,
            $this->payloadFor($action),
        );

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_attaches_the_job_before_running_the_queued_succeeded_hook(): void
    {
        $action = WithHooksRecordingJobState::make()->initialize();

        resolve(\Illuminate\Queue\CallQueuedHandler::class)->call(
            new QueuedJob,
            $this->payloadFor($action),
        );

        $this->assertSame(
            [true],
            Context::get(WithHooksRecordingJobState::SUCCEEDED_RUNNING_IN_QUEUE, [])
        );
    }

    /**
     * @return array<string, string>
     */
    private function payloadFor(Action $action): array
    {
        return [
            'commandName' => $action::class,
            'command' => serialize($action),
        ];
    }
}
