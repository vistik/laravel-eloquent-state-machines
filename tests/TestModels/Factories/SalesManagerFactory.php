<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\Factories;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\SalesManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesManager>
 */
class SalesManagerFactory extends Factory
{
    protected $model = SalesManager::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
        ];
    }
}
