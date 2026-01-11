<?php

namespace App\Services\Booking;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class TemporaryReservationService
{
    protected const KEY_PREFIX = 'temp_booking';

    protected const SLOT_INDEX_PREFIX = 'temp_booking_slot_index';

    /**
     * Create a temporary reservation in Redis.
     */
    public function createReservation(int $offeringTimeSlotId, int $offeringId, int $userId, float $totalPrice, ?string $customerNotes = null): string
    {
        $reservationId = Str::uuid()->toString();

        // Main reservation key (using reservation_id directly)
        $mainKey = $this->makeMainKey($reservationId);

        // Index key for slot lookup (for counting reservations per slot)
        $slotIndexKey = $this->makeSlotIndexKey($offeringTimeSlotId);

        $data = json_encode([
            'offering_time_slot_id' => $offeringTimeSlotId,
            'offering_id' => $offeringId,
            'user_id' => $userId,
            'total_price' => $totalPrice,
            'customer_notes' => $customerNotes,
            'reserved_at' => now()->toIso8601String(),
            'reservation_id' => $reservationId,
        ]);

        // Store main reservation with TTL using dedicated bookings connection (no prefix)
        $ttl = config('booking.reservation.ttl');
        Redis::connection('bookings')->setex($mainKey, $ttl, $data);

        // Add reservation_id to slot index set with TTL
        Redis::connection('bookings')->sadd($slotIndexKey, $reservationId);
        Redis::connection('bookings')->expire($slotIndexKey, $ttl);

        return $reservationId;
    }

    /**
     * Get reservation data by reservation ID.
     */
    public function getReservation(string $reservationId): ?array
    {
        $key = $this->makeMainKey($reservationId);
        $data = Redis::connection('bookings')->get($key);

        return $data ? json_decode($data, true) : null;
    }

    /**
     * Remove a temporary reservation.
     */
    public function removeReservation(string $reservationId): bool
    {
        // Get reservation data first to find the slot
        $reservation = $this->getReservation($reservationId);

        if (! $reservation) {
            return false;
        }

        // Remove from main key
        $mainKey = $this->makeMainKey($reservationId);
        Redis::connection('bookings')->del($mainKey);

        // Remove from slot index
        $slotIndexKey = $this->makeSlotIndexKey($reservation['offering_time_slot_id']);
        Redis::connection('bookings')->srem($slotIndexKey, $reservationId);

        return true;
    }

    /**
     * Count temporary reservations for a specific time slot.
     */
    public function countReservationsForSlot(int $offeringTimeSlotId): int
    {
        $slotIndexKey = $this->makeSlotIndexKey($offeringTimeSlotId);
        $reservationIds = Redis::connection('bookings')->smembers($slotIndexKey);

        // Count only non-expired reservations
        $validCount = 0;
        foreach ($reservationIds as $reservationId) {
            $mainKey = $this->makeMainKey($reservationId);
            if (Redis::connection('bookings')->exists($mainKey)) {
                $validCount++;
            } else {
                // Clean up expired reservation from index
                Redis::connection('bookings')->srem($slotIndexKey, $reservationId);
            }
        }

        return $validCount;
    }

    /**
     * Check if user already has a reservation for this slot.
     */
    public function hasReservation(int $offeringTimeSlotId, int $userId): bool
    {
        $slotIndexKey = $this->makeSlotIndexKey($offeringTimeSlotId);
        $reservationIds = Redis::connection('bookings')->smembers($slotIndexKey);

        foreach ($reservationIds as $reservationId) {
            $reservation = $this->getReservation($reservationId);
            if ($reservation && $reservation['user_id'] == $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Make main Redis key for reservation.
     */
    protected function makeMainKey(string $reservationId): string
    {
        return self::KEY_PREFIX.":{$reservationId}";
    }

    /**
     * Make slot index key for tracking reservations per slot.
     */
    protected function makeSlotIndexKey(int $offeringTimeSlotId): string
    {
        return self::SLOT_INDEX_PREFIX.":{$offeringTimeSlotId}";
    }

    /**
     * Get TTL for reservations (in seconds).
     */
    public function getTTL(): int
    {
        return config('booking.reservation.ttl');
    }
}
