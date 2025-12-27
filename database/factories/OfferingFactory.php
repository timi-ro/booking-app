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

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(3),
            'price' => fake()->randomFloat(2, 10, 500),
            'address_info' => fake()->address() . ', ' . fake()->city() . ' ' . fake()->postcode(),
        ];
    }

    /**
     * Associate the offering with a specific user.
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Set a specific price for the offering.
     */
    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }
}
