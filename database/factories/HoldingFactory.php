<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Holding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holding>
 */
class HoldingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'symbol' => strtoupper(fake()->unique()->lexify('????')),
            'quantity' => fake()->numberBetween(1, 100),
        ];
    }
}
