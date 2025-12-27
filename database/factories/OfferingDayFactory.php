<?php

namespace Database\Factories;

use App\Models\Offering;
use App\Models\OfferingDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OfferingDay>
 */
class OfferingDayFactory extends Factory
{
    protected $model = OfferingDay::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $dayCounter = 0;

        // Generate unique dates by incrementing from tomorrow
        // Each day is 1 day apart, starting from +1 day
        $daysFromNow = 1 + $dayCounter;
        $dayCounter++;

        return [
            'offering_id' => Offering::factory(),
            'date' => now()->addDays($daysFromNow)->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Associate the offering day with a specific offering.
     */
    public function forOffering(int $offeringId): static
    {
        return $this->state(fn (array $attributes) => [
            'offering_id' => $offeringId,
        ]);
    }

    /**
     * Set a specific date for the offering day.
     */
    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }

    /**
     * Add notes to the offering day.
     */
    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }
}
