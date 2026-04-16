<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

use Illuminate\Pipeline\Pipeline;
use Support\Actions\Contracts\Action;
use Support\Actions\Middleware\RunFailed;
use Support\Actions\Middleware\RunSucceeded;

class Dispatcher implements \Illuminate\Contracts\Bus\QueueingDispatcher
{
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
         * `dispatchSync` sets `$command->job` and eventually calls to `dispatchNow`, we need
         * to avoid duplicating calls to `prepare()` and `prependMiddleware()` and can
         * safely send to the decorated dispatcher as we've already prepared it.
         */
        return match (! $command instanceof Action || $command->job) {
            true => $this->decorated->dispatchNow($command, $handler),
            false => (new Pipeline(app()))->send(
                $this->prepareToDispatch($command)->prependMiddleware([RunSucceeded::class, RunFailed::class], $command)
            )->through(
                $command->middleware
            )->finally(
                fn () => $command->job = null
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
            fn () => $this->prepareToDispatch($command)->prependMiddleware([RunSucceeded::class], $command)
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
            fn () => $this->prepareToDispatch($command)->prependMiddleware([RunSucceeded::class], $command),
        );

        return $this->decorated->dispatchSync($command, $handler);
    }

    /**
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command)
    {
        return $this->decorated->hasCommandHandler($command);
    }

    /**
     * @param  mixed  $command
     * @return mixed
     */
    public function getCommandHandler($command)
    {
        return $this->decorated->getCommandHandler($command);
    }

    /**
     * @param  array<mixed>  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes)
    {
        $this->decorated->pipeThrough($pipes);

        return $this;
    }

    /**
     * @param  array<mixed>  $map
     * @return $this
     */
    public function map(array $map)
    {
        $this->decorated->map($map);

        return $this;
    }

    public function findBatch(string $batchId)
    {
        return $this->decorated->findBatch($batchId);
    }

    /**
     * @param  \Illuminate\Support\Collection<array-key, mixed>|array<mixed>  $jobs
     * @return \Illuminate\Bus\PendingBatch
     *
     * @phpstan-ignore method.childParameterType (interface does not declare Collection generic types)
     */
    public function batch($jobs)
    {
        return $this->decorated->batch($jobs);
    }

    /**
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchToQueue($command)
    {
        return $this->decorated->dispatchToQueue($command);
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->decorated->$method(...$parameters);
    }

    private function prepareToDispatch(Action $command): self
    {
        when(
            method_exists($command, 'prepare'),
            fn () => call_user_func([$command, 'prepare'])
        );

        return $this;
    }

    /**
     * @param  array<string>  $classes
     */
    private function prependMiddleware(array $classes, Action $command): Action
    {
        return $command->through([
            ...$classes,
            ...$this->removeMiddleware($classes, $command),
        ]);
    }

    /**
     * @param  array<string>  $classes
     */
    private function appendMiddleware(array $classes, Action $command): void
    {
        $command->middleware = [
            ...$this->removeMiddleware($classes, $command),
            ...$classes,
        ];
    }

    /**
     * @param  array<string>  $classes
     * @return array<mixed>
     */
    private function removeMiddleware(array $classes, Action $command): array
    {
        return array_filter($command->middleware, fn ($m) => match (true) {
            is_string($m) => ! in_array($m, $classes, true),
            is_object($m) => ! in_array($m::class, $classes, true),
            default => true,
        });
    }
}
