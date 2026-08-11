<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

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

        $dispatchable = (clone $command)->standalone();

        try {
            $result = (new DetectsInterruption(app()))->send(
                $command
            )->through(
                array_merge(
                    method_exists($command, 'middleware') ? $command->middleware() : [],
                    $command->middleware ?? []
                )
            )->then(
                fn ($command) => $this->decorated->dispatchNow($command, $handler)
            );

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
