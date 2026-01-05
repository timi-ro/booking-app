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

    public function definition(): array
    {
        static $dayCounter = 0;

        $daysFromNow = 1 + $dayCounter;
        $dayCounter++;

        return [
            'offering_id' => Offering::factory(),
            'date' => now()->addDays($daysFromNow)->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forOffering(int $offeringId): static
    {
        return $this->state(fn (array $attributes) => [
            'offering_id' => $offeringId,
        ]);
    }
}
