<?php

namespace App\Booking\Listeners;

use App\Booking\Events\PaymentSuccessEvent;
use App\Booking\Exceptions\ReservationAlreadyPaidException;
use App\Booking\Exceptions\ReservationExpiredException;
use App\Booking\Repositories\Contracts\BookingRepositoryInterface;
use App\Offering\Repositories\Contracts\OfferingTimeSlotRepositoryInterface;
use App\Booking\Services\BookingService;
use App\Booking\Services\TemporaryReservationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PaymentSuccessListener
{
    public function __construct(
        protected TemporaryReservationService $reservationService,
        protected BookingRepositoryInterface $bookingRepository,
        protected OfferingTimeSlotRepositoryInterface $timeSlotRepository,
        protected BookingService $bookingService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PaymentSuccessEvent $event): void
    {
        Log::info('PaymentSuccessListener: Starting to process payment', [
            'reservation_id' => $event->reservationId,
            'payment_id' => $event->paymentId,
        ]);

        // Check if this reservation has already been paid
        $alreadyPaidKey = "reservation_paid:{$event->reservationId}";
        $alreadyPaid = Redis::connection('bookings')->get($alreadyPaidKey);

        if ($alreadyPaid) {
            Log::warning('Attempt to pay already paid reservation', [
                'reservation_id' => $event->reservationId,
                'payment_id' => $event->paymentId,
            ]);
            throw new ReservationAlreadyPaidException();
        }

        // Get reservation data from Redis
        $reservation = $this->reservationService->getReservation($event->reservationId);

        Log::info('PaymentSuccessListener: Retrieved reservation from Redis', [
            'reservation_id' => $event->reservationId,
            'found' => $reservation !== null,
        ]);

        if (! $reservation) {
            Log::error('Reservation not found or expired', [
                'reservation_id' => $event->reservationId,
                'payment_id' => $event->paymentId,
            ]);
            throw new ReservationExpiredException();
        }

        try {
            // Merge customer notes: prefer notes from payment event, fall back to reservation notes
            $customerNotes = $event->customerNotes ?? $reservation['customer_notes'] ?? null;

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
                'customer_notes' => $customerNotes,
                'confirmed_at' => now(),
            ];

            $booking = $this->bookingRepository->create($bookingData);
            Log::info('PaymentSuccessListener: Booking created in database', [
                'booking_id' => $booking['id'] ?? 'unknown',
            ]);

            // Increment booked_count in offering_time_slots
            $this->timeSlotRepository->incrementBookedCount($reservation['offering_time_slot_id']);
            Log::info('PaymentSuccessListener: Incremented booked_count');

            // Remove temporary reservation from Redis
            $removed = $this->reservationService->removeReservation($event->reservationId);
            Log::info('PaymentSuccessListener: Removed reservation from Redis', [
                'success' => $removed,
            ]);

            // Mark reservation as paid (store for 24 hours to prevent double payment)
            $alreadyPaidKey = "reservation_paid:{$event->reservationId}";
            Redis::connection('bookings')->setex($alreadyPaidKey, 86400, json_encode([
                'booking_reference' => $bookingData['booking_reference'],
                'payment_id' => $event->paymentId,
                'paid_at' => now()->toIso8601String(),
            ]));

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
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
