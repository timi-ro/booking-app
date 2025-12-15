<?php

namespace App\Listeners;

use App\Events\PaymentSuccessEvent;
use App\Exceptions\Booking\ReservationExpiredException;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\OfferingTimeSlotRepositoryInterface;
use App\Services\Booking\BookingService;
use App\Services\Booking\TemporaryReservationService;
use Illuminate\Support\Facades\Log;

class PaymentSuccessListener
{
    public function __construct(
        protected TemporaryReservationService $reservationService,
        protected BookingRepositoryInterface $bookingRepository,
        protected OfferingTimeSlotRepositoryInterface $timeSlotRepository,
        protected BookingService $bookingService,
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentSuccessEvent $event): void
    {
        // Get reservation data from Redis
        $reservation = $this->reservationService->getReservation($event->reservationId);

        if (!$reservation) {
            Log::error('Reservation not found or expired', [
                'reservation_id' => $event->reservationId,
                'payment_id' => $event->paymentId,
            ]);
            throw new ReservationExpiredException();
        }

        try {
            // Create permanent booking in MySQL
            $bookingData = [
                'offering_time_slot_id' => $reservation['offering_time_slot_id'],
                'offering_id' => $reservation['offering_id'],
                'user_id' => $reservation['user_id'],
                'booking_reference' => $this->bookingService->generateBookingReference(),
                'status' => 'confirmed',
                'total_price' => $reservation['total_price'],
                'payment_status' => 'paid',
                'payment_id' => $event->paymentId,
                'customer_notes' => $event->customerNotes,
                'confirmed_at' => now(),
            ];

            $this->bookingRepository->create($bookingData);

            // Increment booked_count in offering_time_slots
            $this->timeSlotRepository->incrementBookedCount($reservation['offering_time_slot_id']);

            // Remove temporary reservation from Redis
            $this->reservationService->removeReservation($event->reservationId);

            Log::info('Booking confirmed successfully', [
                'reservation_id' => $event->reservationId,
                'payment_id' => $event->paymentId,
                'booking_reference' => $bookingData['booking_reference'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to finalize booking', [
                'reservation_id' => $event->reservationId,
                'payment_id' => $event->paymentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
