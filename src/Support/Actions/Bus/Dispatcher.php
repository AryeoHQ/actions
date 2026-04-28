<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

use Illuminate\Pipeline\Pipeline;
use Support\Actions\Contracts\Action;

class Dispatcher implements \Illuminate\Contracts\Bus\QueueingDispatcher
{
    use Concerns\ForwardsCalls;

    private readonly \Illuminate\Contracts\Bus\QueueingDispatcher $decorated;

    public function __construct(\Illuminate\Contracts\Bus\QueueingDispatcher $dispatcher)
    {
        $this->decorated = $dispatcher;
    }

    /**
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null)
    {
        /**
         * Laravel's `dispatchNow()` sets `$command->job` before executing. The `$command->job`
         * check acts as a re-entry guard so we only prepare and wrap the command with lifecycle
         * middleware once. The `->finally()` below clears it after the pipeline completes,
         * allowing for subsequent execution of the same command instance if needed.
         */
        return match (! $command instanceof Action || $command->job) {
            true => $this->decorated->dispatchNow($command, $handler),
            false => (new Pipeline(app()))->send(
                $command->prepareFor(Invocation::Now)
            )->through(
                $command->middleware
            )->finally(
                fn () => $command->clearJob()
            )->then(
                fn ($command) => $this->decorated->dispatchNow($command, $handler)
            ),
        };
    }

    /**
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command)
    {
        when(
            $command instanceof Action,
            fn () => $command->prepareFor(Invocation::Dispatch)
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
            fn () => $command->prepareFor(Invocation::Sync)
        );

        return $this->decorated->dispatchSync($command, $handler);
    }
}
