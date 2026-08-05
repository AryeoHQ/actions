<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Actions\Queue;

use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;

// A non-SyncJob so CallQueuedHandler takes its queued branch.
final class QueuedJob extends Job implements JobContract
{
    public function __construct(bool $failed = false, bool $released = false)
    {
        $this->failed = $failed;
        $this->released = $released;
    }

    public function getJobId(): string
    {
        return 'queued';
    }

    public function getRawBody(): string
    {
        return '';
    }

    public function attempts(): int
    {
        return 1;
    }
}
