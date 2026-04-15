<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

use Support\Actions\Contracts\Action;

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
        if (! $command instanceof Action || $command->job) {
            return $this->decorated->dispatchNow($command, $handler);
        }

        try {
            $result = $this->decorated->dispatchNow($command, $handler);
        } catch (\Throwable $throwable) {
            $this->runHook($command, 'failed', $throwable);

            throw $throwable;
        }

        $this->runHook($command, 'succeeded');

        return $result;
    }

    private function runHook(object $command, string $method, mixed ...$arguments): void
    {
        if (method_exists($command, $method)) {
            rescue(fn () => $command->$method(...$arguments), report: true);
        }
    }

    /**
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command)
    {
        return $this->decorated->dispatch($command);
    }

    /**
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchSync($command, $handler = null)
    {
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
     * @param  \Illuminate\Support\Collection|array<mixed>  $jobs
     * @return \Illuminate\Bus\PendingBatch
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
}
