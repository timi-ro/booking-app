<?php

namespace Database\Factories;

use App\Models\Offering;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offering>
 */
class OfferingFactory extends Factory
{
    protected $model = Offering::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(4),
            'price' => fake()->randomFloat(2, 10, 500),
            'address_info' => fake()->address() . ', ' . fake()->city() . ' ' . fake()->postcode(),
        ];
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
