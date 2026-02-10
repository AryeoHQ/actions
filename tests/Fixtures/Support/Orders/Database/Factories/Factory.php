<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders\Database\Factories;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Tests\Fixtures\Support\Orders\Order>
 */
class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = \Tests\Fixtures\Support\Orders\Order::class;

    public function definition()
    {
        return [
            'name' => fake()->word(),
        ];
    }
}
