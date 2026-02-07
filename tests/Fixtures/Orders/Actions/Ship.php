<?php

declare(strict_types=1);

namespace Tests\Fixtures\Orders\Actions;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class Ship implements Action
{
    use AsAction;
    // TODO: Action catches ShouldQueue & Dispatachable prevention
    // TODO: AsAction does not, figure out why

    public readonly string $input;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function handle(): string
    {
        return $this->input.' charged';
    }
}
