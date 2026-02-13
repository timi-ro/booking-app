<?php

namespace Database\Factories;

use App\Offering\Models\OfferingDay;
use App\Offering\Models\OfferingTimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Offering\Models\OfferingTimeSlot>
 */
class OfferingTimeSlotFactory extends Factory
{
    protected $model = OfferingTimeSlot::class;

    public function definition(): array
    {
        static $slotCounter = 0;

        $startHour = 9 + ($slotCounter % 8); // 9:00 to 16:00
        $slotCounter++;

        $startTime = sprintf('%02d:00', $startHour);
        $endTime = sprintf('%02d:00', $startHour + 1);

        return [
            'offering_day_id' => OfferingDay::factory(),
            'offering_id' => function (array $attributes) {
                return OfferingDay::find($attributes['offering_day_id'])->offering_id;
            },
            'start_time' => $startTime,
            'end_time' => $endTime,
            'capacity' => fake()->numberBetween(5, 20),
            'booked_count' => 0,
            'price_override' => null,
        ];
    }

    public function forOfferingDay(int $offeringDayId): static
    {
        return $this->state(function (array $attributes) use ($offeringDayId) {
            $offeringDay = OfferingDay::find($offeringDayId);

            return [
                'offering_day_id' => $offeringDayId,
                'offering_id' => $offeringDay->offering_id,
            ];
        });
    }

    public function withCapacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => $capacity,
        ]);
    }

    public function withPriceOverride(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price_override' => $price,
        ]);
    }

    public function fullyBooked(): static
    {
        return $this->state(fn (array $attributes) => [
            'booked_count' => $attributes['capacity'] ?? 10,
        ]);
    }

    public function withTime(string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }
}
