<?php

namespace App\Services\Booking;

use App\Exceptions\Booking\BookingAlreadyCancelledException;
use App\Exceptions\Booking\BookingNotFoundException;
use App\Exceptions\Booking\SlotFullyBookedException;
use App\Exceptions\OfferingTimeSlot\OfferingTimeSlotNotFoundException;
use App\Exceptions\UnauthorizedAccessException;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\OfferingRepositoryInterface;
use App\Repositories\Contracts\OfferingTimeSlotRepositoryInterface;
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
            throw new UnauthorizedAccessException();
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
            throw new UnauthorizedAccessException();
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
            throw new UnauthorizedAccessException();
        }

        // Cancel booking
        $this->bookingRepository->cancel($bookingId, $reason);

        // Free up capacity
        $this->timeSlotRepository->decrementBookedCount($booking['offering_time_slot_id']);

        // Mock refund (set payment status to refunded)
        $this->bookingRepository->update($bookingId, ['payment_status' => 'refunded']);
    }

    /**
     * Generate unique booking reference.
     */
    public function generateBookingReference(): string
    {
        return 'BOOK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
