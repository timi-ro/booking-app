<?php

namespace Database\Factories;

use App\Models\OfferingDay;
use App\Models\OfferingTimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OfferingTimeSlot>
 */
class OfferingTimeSlotFactory extends Factory
{
    protected $model = OfferingTimeSlot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $slotCounter = 0;

        // Generate unique time slots using a counter
        // Each slot is 1 hour, starting from 9:00
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

    /**
     * Associate the time slot with a specific offering day.
     */
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

    /**
     * Set a specific capacity for the time slot.
     */
    public function withCapacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => $capacity,
        ]);
    }

    /**
     * Set a price override for the time slot.
     */
    public function withPriceOverride(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price_override' => $price,
        ]);
    }

    /**
     * Mark the time slot as fully booked.
     */
    public function fullyBooked(): static
    {
        return $this->state(fn (array $attributes) => [
            'booked_count' => $attributes['capacity'] ?? 10,
        ]);
    }

    /**
     * Set specific time for the slot.
     */
    public function withTime(string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }
}
