<?php

declare(strict_types=1);

namespace Support\Actions\Middleware\Lifecycle;

use Support\Actions\Bus\Pipelines\Exceptions\Interrupted;
use Support\Actions\Middleware\Lifecycle\Contracts\Lifecycle;
use Throwable;

class RunFailed implements Lifecycle
{
    public function handle(object $command, callable $next): mixed
    {
        try {
            return $next($command);
        } catch (Interrupted $interrupted) {
            throw $interrupted;
        } catch (Throwable $throwable) {
            $this->run($command, $throwable);

            throw $throwable;
        }
    }

    private function run(object $command, Throwable $throwable): void
    {
        when(
            $this->shouldRun($command),
            fn () => rescue(fn () => call_user_func([$command, 'failed'], $throwable), report: true)
        );
    }

    private function shouldRun(object $command): bool
    {
        return method_exists($command, 'failed');
    }
}
