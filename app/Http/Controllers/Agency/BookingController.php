<?php

namespace App\Http\Controllers\Agency;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\ListBookingsRequest;
use App\Services\Booking\BookingService;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Agency - Bookings
 *
 * APIs for agencies to manage bookings for their offerings.
 */
class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
    ) {}

    /**
     * List agency's bookings
     *
     * Retrieve all bookings for the agency's offerings with optional filtering and pagination.
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

        $bookings = $this->bookingService->getAgencyBookingsWithFilters($userId, $validated);

        return ResponseHelper::generateResponse($bookings);
    }

    /**
     * Get a specific booking
     *
     * Retrieve details of a booking for one of the agency's offerings.
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
     *     "user": {},
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
     * Agency can cancel bookings for their offerings. The time slot capacity will be freed up and payment refunded.
     *
     * @urlParam id integer required The booking ID to cancel. Example: 1
     *
     * @bodyParam cancellation_reason string Optional reason for cancellation. Example: Offering no longer available
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

    /**
     * Mark booking as no-show
     *
     * Agency can mark a booking as no-show when the customer doesn't show up for their appointment.
     * This can only be done after the booking time has passed. The customer's payment is retained
     * (no refund for no-shows), but the time slot capacity is freed up.
     *
     * @urlParam id integer required The booking ID to mark as no-show. Example: 1
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "success",
     *   "data": {
     *     "message": "Booking marked as no-show successfully"
     *   }
     * }
     * @response 400 {
     *   "code": 400,
     *   "message": "Cannot mark as no-show - booking time has not passed yet",
     *   "data": null
     * }
     */
    public function markNoShow(int $id)
    {
        $userId = auth()->id();

        $this->bookingService->markAsNoShow($id, $userId);

        return ResponseHelper::generateResponse(['message' => 'Booking marked as no-show successfully']);
    }
}
