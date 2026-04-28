<?php

declare(strict_types=1);

namespace Support\Actions\Bus\Concerns;

/**
 * @mixin \Support\Actions\Bus\Dispatcher
 */
trait ForwardsCalls
{
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
}
