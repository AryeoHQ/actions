<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

enum Invocation
{
    case Now;

    case Dispatch;

    case Sync;
}
