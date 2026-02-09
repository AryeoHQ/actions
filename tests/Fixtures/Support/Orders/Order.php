<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Orders;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Support\Orders\Database\Factories\Factory;

/**
 * @property int $id
 * @property string $name
 */
#[UseFactory(Factory::class)]
class Order extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];
}
