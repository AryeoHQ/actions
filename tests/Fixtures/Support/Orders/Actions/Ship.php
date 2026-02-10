<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Actions;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Tests\Fixtures\Support\Orders\Order;
use Tests\Fixtures\Support\Orders\Services\Shipping;

final class Ship implements Action
{
    use AsAction;

    public readonly Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(Shipping $service): string
    {
        $notify = Notify::make()->now();

        return $this->order->name.': '.$service->process().' and '.$notify;
    }
}
