<?php

declare(strict_types=1);

namespace Tests\Fixtures\Orders\Actions;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class Archive implements Action
{
    use AsAction;

    public readonly string $input;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function handle(): string
    {
        return $this->input . ' archived';
    }
}
