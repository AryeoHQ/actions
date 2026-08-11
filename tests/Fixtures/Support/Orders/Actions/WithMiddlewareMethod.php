<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Tests\Fixtures\Support\Orders\Middleware\WritesToContext;
use Tests\Fixtures\Support\Orders\Order;

final class WithMiddlewareMethod implements Action
{
    use AsAction;

    public readonly Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /** @return array<int, class-string> */
    public function middleware(): array
    {
        return [WritesToContext::class];
    }

    public function handle(): string
    {
        return $this->order->name.': archived';
    }
}
