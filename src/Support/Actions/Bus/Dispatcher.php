<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Support\Actions\Bus\Exceptions\Prevented;
use Support\Actions\Contracts\Action;
use Support\Actions\Pipeline\DetectsInterruption;
use Support\Actions\Pipeline\Exceptions\Interrupted;
use Throwable;

class Dispatcher implements \Illuminate\Contracts\Bus\QueueingDispatcher
{
    use Concerns\ForwardsCalls;

    private readonly \Illuminate\Contracts\Bus\QueueingDispatcher $decorated;

    public function __construct(\Illuminate\Contracts\Bus\QueueingDispatcher $dispatcher)
    {
        $this->decorated = $dispatcher;
    }

    /**
     * When processing a queued job, the handler calls dispatchNow() with $command->job
     * already set. In that case we want to just pass it through since the handler
     * has its own lifecycle machinery.
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null)
    {
        return match (true) {
            $command instanceof Action && ! $command->job => $this->dispatchNowThroughLifecycle($command, $handler),
            default => $this->decorated->dispatchNow($command, $handler),
        };
    }

    /**
     * @param  mixed  $handler
     * @return mixed
     */
    private function dispatchNowThroughLifecycle(Action $command, $handler)
    {
        $command->initialize();

        // Reading uniqueId to acquire the lock memoizes it onto $command before the snapshot,
        // so a re-dispatched copy carries the same identity instead of generating a new one.
        $this->acquireUniqueLock($command);

        try {
            // If the snapshot throws (a custom clone/standalone), the lifecycle never runs, so its
            // release never fires — release here or the lock leaks with no expiry.
            $dispatchable = (clone $command)->standalone();
        } catch (Throwable $throwable) {
            $this->releaseUniqueLock($command);

            throw $throwable;
        }

        try {
            $result = $this->runThroughLifecycle($command, $handler);

            $this->succeeded($command, $dispatchable);

            return $result;
        } catch (Interrupted $interrupted) {
            throw $interrupted;
        } catch (Throwable $throwable) {
            $this->failed($command, $dispatchable, $throwable);

            throw $throwable;
        } finally {
            $command->clearJob();
        }
    }

    /**
     * @param  mixed  $handler
     * @return mixed
     */
    private function runThroughLifecycle(Action $command, $handler)
    {
        // now() uses bare dispatchNow(), which skips the queue's unique-lock handling, so we reproduce
        // it here. Release timing mirrors Illuminate\Queue\CallQueuedHandler::dispatchThroughMiddleware
        // (diff against it on Laravel upgrades): until-processing releases before handle(); the flag —
        // its $lockReleased — stops the after-handle release from force-freeing a concurrent run's lock.
        $released = false;

        try {
            return (new DetectsInterruption(app()))->send(
                $command
            )->through(
                array_merge(
                    method_exists($command, 'middleware') ? $command->middleware() : [],
                    $command->middleware ?? []
                )
            )->then(function (Action $command) use ($handler, &$released) {
                if ($command instanceof ShouldBeUniqueUntilProcessing) {
                    $this->releaseUniqueLock($command);

                    $released = true;
                }

                return $this->decorated->dispatchNow($command, $handler);
            });
        } finally {
            if (! $released) {
                $this->releaseUniqueLock($command);
            }
        }
    }

    private function acquireUniqueLock(Action $command): void
    {
        if (! $command instanceof ShouldBeUnique) {
            return;
        }

        throw_unless(
            (new UniqueLock(app(Cache\Repository::class)))->acquire($command),
            fn () => Prevented::for($command::class)
        );
    }

    private function releaseUniqueLock(Action $command): void
    {
        when(
            $command instanceof ShouldBeUnique,
            fn () => (new UniqueLock(app(Cache\Repository::class)))->release($command)
        );
    }

    /**
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command)
    {
        when(
            $command instanceof Action,
            fn () => $command->initialize()
        );

        return $this->decorated->dispatch($command);
    }

    /**
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchSync($command, $handler = null)
    {
        when(
            $command instanceof Action,
            fn () => $command->initialize()
        );

        return $this->decorated->dispatchSync($command, $handler);
    }

    private function succeeded(Action $command, Action $dispatchable): void
    {
        if ($command->failedOrReleased) {
            return;
        }

        when(
            method_exists($command, 'succeeded'),
            fn () => rescue(fn () => call_user_func([$command, 'succeeded']), report: true)
        );

        when(
            $command->dispatchesAfterSucceeded,
            fn () => rescue(fn () => $dispatchable->dispatch(), report: true) // @phpstan-ignore argument.templateType
        );
    }

    private function failed(Action $command, Action $dispatchable, Throwable $throwable): void
    {
        when(
            method_exists($command, 'failed'),
            fn () => rescue(fn () => call_user_func([$command, 'failed'], $throwable), report: true)
        );

        when(
            $command->dispatchesAfterFailed,
            fn () => rescue(fn () => $dispatchable->dispatch(), report: true) // @phpstan-ignore argument.templateType
        );
    }
}
