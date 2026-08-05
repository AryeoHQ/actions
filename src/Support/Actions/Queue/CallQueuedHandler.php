<?php

declare(strict_types=1);

namespace Support\Actions\Queue;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Jobs\SyncJob;
use Support\Actions\Attributes\DispatchAfterQueuedFailed;
use Support\Actions\Attributes\DispatchAfterQueuedSucceeded;
use Support\Actions\Attributes\DispatchAfterSyncFailed;
use Support\Actions\Attributes\DispatchAfterSyncSucceeded;
use Support\Actions\Bus\Invocation;
use Support\Actions\Contracts\Action;

class CallQueuedHandler extends \Illuminate\Queue\CallQueuedHandler
{
    private readonly \Illuminate\Queue\CallQueuedHandler $decorated;

    public function __construct(\Illuminate\Queue\CallQueuedHandler $handler, Dispatcher $dispatcher, Container $container) // @phpstan-ignore ergebnis.noParameterWithContainerTypeDeclaration
    {
        parent::__construct($dispatcher, $container);

        $this->decorated = $handler;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function call(Job $job, array $data): void
    {
        $this->decorated->call($job, $data);

        // The framework has no success hook, we re-attach the job to match the framework's behavior on the failed hook.
        when(
            ! $job->hasFailed() && ! $job->isReleased() && $this->isAction($data),
            fn () => $this->afterSuccess($this->setJobInstanceIfNecessary($job, $this->getCommand($data)), $job)
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function failed(array $data, $e, string $uuid, null|Job $job = null): void
    {
        try {
            $this->decorated->failed($data, $e, $uuid, $job);
        } finally { // Ensure re-dispatch even if the job's failed() throws.
            when(
                $this->isAction($data),
                fn () => $this->afterFailure($this->getCommand($data), $job)
            );
        }
    }

    private function afterSuccess(Action $action, Job $job): void
    {
        when(
            method_exists($action, 'succeeded'),
            fn () => rescue(fn () => call_user_func([$action, 'succeeded']), report: true)
        );

        $this->redispatch($action, $job, failed: false);
    }

    private function afterFailure(Action $action, null|Job $job): void
    {
        $this->redispatch($action, $job, failed: true);
    }

    private function redispatch(Action $action, null|Job $job, bool $failed): void
    {
        $attribute = match (true) {
            ! $job instanceof SyncJob => match ($failed) {
                true => DispatchAfterQueuedFailed::class,
                false => DispatchAfterQueuedSucceeded::class,
            },
            $action->invokedVia === Invocation::Sync => match ($failed) {
                true => DispatchAfterSyncFailed::class,
                false => DispatchAfterSyncSucceeded::class,
            },
            default => null,
        };

        when(
            $attribute !== null && $action->declares($attribute),
            fn () => rescue(fn () => (clone $action)->standalone()->dispatch(), report: true) // @phpstan-ignore argument.templateType
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function isAction(array $data): bool
    {
        return is_string($data['commandName'] ?? null)
            && is_subclass_of($data['commandName'], Action::class);
    }
}
