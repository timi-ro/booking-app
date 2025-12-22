<?php

namespace App\Services\Booking;

use App\Exceptions\Booking\BookingAlreadyCancelledException;
use App\Exceptions\Booking\BookingNotFoundException;
use App\Exceptions\Booking\BookingTimeNotPassedException;
use App\Exceptions\Booking\SlotFullyBookedException;
use App\Exceptions\OfferingTimeSlot\OfferingTimeSlotNotFoundException;
use App\Exceptions\User\AuthenticationException;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\OfferingRepositoryInterface;
use App\Repositories\Contracts\OfferingTimeSlotRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository,
        protected OfferingTimeSlotRepositoryInterface $timeSlotRepository,
        protected OfferingRepositoryInterface $offeringRepository,
        protected TemporaryReservationService $reservationService,
    ) {
    }

    /**
     * Create a temporary reservation for a time slot.
     */
    public function createReservation(int $offeringTimeSlotId, int $userId, ?string $customerNotes = null): array
    {
        $timeSlot = $this->timeSlotRepository->findById($offeringTimeSlotId);

        if (!$timeSlot) {
            throw new OfferingTimeSlotNotFoundException();
        }

        // Check availability
        if (!$this->isSlotAvailable($offeringTimeSlotId)) {
            throw new SlotFullyBookedException();
        }

        // Calculate price
        $offering = $this->offeringRepository->findWhere(['id' => $timeSlot['offering_id']]);
        $totalPrice = $timeSlot['price_override'] ?? $offering['price'];

        // Create temporary reservation in Redis
        $reservationId = $this->reservationService->createReservation(
            $offeringTimeSlotId,
            $timeSlot['offering_id'],
            $userId,
            (float) $totalPrice,
            $customerNotes
        );

        return [
            'reservation_id' => $reservationId,
            'offering_time_slot_id' => $offeringTimeSlotId,
            'offering_id' => $timeSlot['offering_id'],
            'total_price' => $totalPrice,
            'expires_at' => now()->addSeconds($this->reservationService->getTTL())->toIso8601String(),
            'ttl_seconds' => $this->reservationService->getTTL(),
        ];
    }

    /**
     * Check if a slot is available (considering permanent + temp reservations).
     */
    public function isSlotAvailable(int $offeringTimeSlotId): bool
    {
        $timeSlot = $this->timeSlotRepository->findById($offeringTimeSlotId);

        if (!$timeSlot) {
            return false;
        }

        // Count permanent bookings
        $permanentBookings = $this->bookingRepository->countConfirmedForSlot($offeringTimeSlotId);

        // Count temporary reservations
        $tempReservations = $this->reservationService->countReservationsForSlot($offeringTimeSlotId);

        // Calculate available capacity
        $availableCapacity = $timeSlot['capacity'] - ($permanentBookings + $tempReservations);

        return $availableCapacity > 0;
    }

    /**
     * Get user's bookings.
     */
    public function getUserBookings(int $userId, array $filters = []): array
    {
        return $this->bookingRepository->findByUser($userId, $filters);
    }

    /**
     * Get bookings for an offering (agency view).
     */
    public function getOfferingBookings(int $offeringId, int $agencyUserId, array $filters = []): array
    {
        // Verify ownership
        $offering = $this->offeringRepository->findWhere(['id' => $offeringId]);

        if (empty($offering)) {
            throw new OfferingTimeSlotNotFoundException();
        }

        if ($offering['user_id'] != $agencyUserId) {
            throw new AuthenticationException();
        }

        return $this->bookingRepository->findByOffering($offeringId, $filters);
    }

    /**
     * Get all bookings for agency with filters and pagination.
     */
    public function getAgencyBookingsWithFilters(int $agencyUserId, array $filters, int $page, int $pageSize): array
    {
        return $this->bookingRepository->listAllForAgencyWithFilters($agencyUserId, $filters, $page, $pageSize);
    }

    /**
     * Get all bookings for customer with filters and pagination.
     */
    public function getCustomerBookingsWithFilters(int $userId, array $filters, int $page, int $pageSize): array
    {
        return $this->bookingRepository->listAllForCustomerWithFilters($userId, $filters, $page, $pageSize);
    }

    /**
     * Get booking by ID.
     */
    public function getBooking(int $bookingId, int $userId): array
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new BookingNotFoundException();
        }

        // Check authorization (customer can see their own, agency can see their offering's)
        $isCustomer = $booking['user_id'] == $userId;
        $isAgency = $booking['offering']['user_id'] == $userId;

        if (!$isCustomer && !$isAgency) {
            throw new AuthenticationException();
        }

        return $booking;
    }

    /**
     * Cancel a booking (customer or agency).
     */
    public function cancelBooking(int $bookingId, int $userId, ?string $reason = null): void
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new BookingNotFoundException();
        }

        if ($booking['status'] === 'cancelled') {
            throw new BookingAlreadyCancelledException();
        }

        // Check authorization
        $isCustomer = $booking['user_id'] == $userId;
        $isAgency = $booking['offering']['user_id'] == $userId;

        if (!$isCustomer && !$isAgency) {
            throw new AuthenticationException();
        }

        // Cancel booking
        $this->bookingRepository->cancel($bookingId, $reason);

        // Free up capacity
        $this->timeSlotRepository->decrementBookedCount($booking['offering_time_slot_id']);

        // Mock refund (set payment status to refunded)
        $this->bookingRepository->update($bookingId, ['payment_status' => 'refunded']);
    }

    /**
     * Mark a booking as no-show (agency only).
     */
    public function markAsNoShow(int $bookingId, int $agencyUserId): void
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new BookingNotFoundException();
        }

        // Only agency can mark as no-show
        $isAgency = $booking['offering']['user_id'] == $agencyUserId;

        if (!$isAgency) {
            throw new AuthenticationException();
        }

        // Can only mark confirmed bookings as no-show
        if ($booking['status'] !== 'confirmed') {
            throw new BookingAlreadyCancelledException('Cannot mark a ' . $booking['status'] . ' booking as no-show');
        }

        // Booking time must have passed (customer should have shown up by now)
        $bookingStartTime = $booking['time_slot']['start_time'];
        $bookingDate = $booking['time_slot']['offering_day']['date'];

        // Extract just the date part (YYYY-MM-DD) and combine with time
        $dateOnly = Carbon::parse($bookingDate)->format('Y-m-d');
        $bookingDateTime = Carbon::parse($dateOnly . ' ' . $bookingStartTime);

        if ($bookingDateTime->isFuture()) {
            throw new BookingTimeNotPassedException();
        }

        // Update status to no_show
        $this->bookingRepository->update($bookingId, [
            'status' => 'no_show',
        ]);

        // Payment remains (no refund for no-shows)
        // Capacity is freed up since customer didn't use the slot
        $this->timeSlotRepository->decrementBookedCount($booking['offering_time_slot_id']);
    }

    /**
     * Generate unique booking reference.
     */
    public function generateBookingReference(): string
    {
        $prefix = config('booking.reference.prefix');
        $randomLength = config('booking.reference.random_length');

        return $prefix . '-' . now()->format('Ymd') . '-' . strtoupper(Str::random($randomLength));
    }
}
