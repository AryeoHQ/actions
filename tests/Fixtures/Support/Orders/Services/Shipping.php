<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Services;

final class Shipping
{
    public function process(): string
    {
        return 'shipped';
    }
}
