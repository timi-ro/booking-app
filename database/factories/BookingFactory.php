<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Offering;
use App\Models\OfferingTimeSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'offering_time_slot_id' => OfferingTimeSlot::factory(),
            'offering_id' => Offering::factory(),
            'user_id' => User::factory(),
            'booking_reference' => 'BOOK-'.now()->format('Ymd').'-'.strtoupper($this->faker->bothify('???###')),
            'status' => 'confirmed',
            'total_price' => $this->faker->randomFloat(2, 50, 500),
            'payment_status' => 'paid',
            'payment_id' => 'pay_'.$this->faker->uuid(),
            'customer_notes' => $this->faker->optional()->sentence(),
            'cancellation_reason' => null,
            'cancelled_at' => null,
            'confirmed_at' => now(),
        ];
    }

    public function forTimeSlot(int $timeSlotId): static
    {
        return $this->state(function () use ($timeSlotId) {
            $timeSlot = OfferingTimeSlot::find($timeSlotId);

            return [
                'offering_time_slot_id' => $timeSlotId,
                'offering_id' => $timeSlot->offering_id,
            ];
        });
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn () => [
            'user_id' => $userId,
        ]);
    }

    public function forOffering(int $offeringId): static
    {
        return $this->state(fn () => [
            'offering_id' => $offeringId,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'pending',
            'confirmed_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => 'cancelled',
            'cancellation_reason' => $this->faker->sentence(),
            'cancelled_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
