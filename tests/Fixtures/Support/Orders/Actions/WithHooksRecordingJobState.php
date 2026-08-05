<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class WithHooksRecordingJobState implements Action
{
    use AsAction;

    public const SUCCEEDED_RUNNING_IN_QUEUE = self::class.'::succeeded.runningInQueue';

    public function handle(): void {}

    public function succeeded(): void
    {
        Context::push(self::SUCCEEDED_RUNNING_IN_QUEUE, $this->runningInQueue);
    }
}
