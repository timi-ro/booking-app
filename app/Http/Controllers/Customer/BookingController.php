<?php

namespace App\Http\Controllers\Customer;

use App\Events\PaymentSuccessEvent;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\ConfirmPaymentRequest;
use App\Http\Requests\Booking\CreateReservationRequest;
use App\Http\Requests\Booking\ListBookingsRequest;
use App\Services\Booking\BookingService;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Customer - Bookings
 *
 * APIs for managing customer bookings, reservations, and payments.
 */
class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
    ) {}

    /**
     * Create a temporary reservation
     *
     * Reserve a time slot temporarily for 10 minutes. This holds the slot while the customer completes payment.
     * The reservation will automatically expire after 10 minutes if payment is not confirmed.
     *
     * @bodyParam offering_time_slot_id integer required The ID of the time slot to reserve. Example: 1
     * @bodyParam customer_notes string Optional notes from the customer. Example: Please confirm via email
     *
     * @response 201 {
     *   "code": 201,
     *   "message": "success",
     *   "data": {
     *     "reservation_id": "9c8e7f6a-5d4c-3b2a-1e0f-9d8c7b6a5e4f",
     *     "offering_time_slot_id": 1,
     *     "offering_id": 1,
     *     "total_price": "100.00",
     *     "expires_at": "2025-12-15T10:45:00Z",
     *     "ttl_seconds": 600
     *   }
     * }
     */
    public function reserve(CreateReservationRequest $request)
    {
        $validated = $request->validated();
        $userId = auth()->id();

        $reservation = $this->bookingService->createReservation(
            $validated['offering_time_slot_id'],
            $userId,
            $validated['customer_notes'] ?? null
        );

        return ResponseHelper::generateResponse($reservation, Response::HTTP_CREATED);
    }

    /**
     * Confirm payment and finalize booking
     *
     * After completing payment (currently mocked), call this endpoint to finalize the booking.
     * This converts the temporary reservation into a permanent booking.
     *
     * @bodyParam reservation_id string required The reservation ID from the reserve endpoint. Example: 9c8e7f6a-5d4c-3b2a-1e0f-9d8c7b6a5e4f
     * @bodyParam payment_id string required The payment transaction ID. Example: pay_mock_123456
     * @bodyParam customer_notes string Optional additional notes from the customer. Example: Arriving 15 minutes early
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "success",
     *   "data": {
     *     "message": "Payment confirmed. Booking is being processed."
     *   }
     * }
     */
    public function confirmPayment(ConfirmPaymentRequest $request)
    {
        $validated = $request->validated();

        // Fire payment success event
        event(new PaymentSuccessEvent(
            $validated['reservation_id'],
            $validated['payment_id'],
            $validated['customer_notes'] ?? null
        ));

        return ResponseHelper::generateResponse(['message' => 'Payment confirmed. Booking is being processed.']);
    }

    /**
     * List customer's bookings
     *
     * Retrieve all bookings for the authenticated customer with optional filtering and pagination.
     *
     * @queryParam offering_id integer Filter by offering ID. Example: 1
     * @queryParam status string Filter by booking status (confirmed, cancelled, completed, no_show). Example: confirmed
     * @queryParam payment_status string Filter by payment status (pending, paid, refunded). Example: paid
     * @queryParam date_from string Filter bookings from this date (Y-m-d format). Example: 2025-01-01
     * @queryParam date_to string Filter bookings until this date (Y-m-d format). Example: 2025-12-31
     * @queryParam sort_by string Sort by field (created_at, confirmed_at, total_price, status). Example: created_at
     * @queryParam sort_direction string Sort direction (asc, desc). Example: desc
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam page_size integer Number of items per page. Example: 15
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "success",
     *   "data": {
     *     "data": [],
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 50,
     *     "last_page": 4
     *   }
     * }
     */
    public function index(ListBookingsRequest $request)
    {
        $validated = $request->validated();
        $userId = auth()->id();

        $bookings = $this->bookingService->getCustomerBookingsWithFilters($userId, $validated);

        return ResponseHelper::generateResponse($bookings);
    }

    /**
     * Get a specific booking
     *
     * Retrieve details of a single booking by ID.
     *
     * @urlParam id integer required The booking ID. Example: 1
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "success",
     *   "data": {
     *     "id": 1,
     *     "booking_reference": "BOOK-20251215-ABC123",
     *     "status": "confirmed",
     *     "payment_status": "paid",
     *     "total_price": "100.00",
     *     "offering": {},
     *     "time_slot": {}
     *   }
     * }
     */
    public function show(int $id)
    {
        $userId = auth()->id();

        $booking = $this->bookingService->getBooking($id, $userId);

        return ResponseHelper::generateResponse($booking);
    }

    /**
     * Cancel a booking
     *
     * Cancel an existing booking and process refund. The time slot capacity will be freed up.
     *
     * @urlParam id integer required The booking ID to cancel. Example: 1
     *
     * @bodyParam cancellation_reason string Optional reason for cancellation. Example: Change of plans
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "success",
     *   "data": {
     *     "message": "Booking cancelled successfully"
     *   }
     * }
     */
    public function cancel(CancelBookingRequest $request, int $id)
    {
        $validated = $request->validated();
        $userId = auth()->id();

        $this->bookingService->cancelBooking(
            $id,
            $userId,
            $validated['cancellation_reason'] ?? null
        );

        return ResponseHelper::generateResponse(['message' => 'Booking cancelled successfully']);
    }
}
