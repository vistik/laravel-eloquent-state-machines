<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\Factories;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [];
    }
}
