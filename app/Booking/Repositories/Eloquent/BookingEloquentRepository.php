<?php

namespace App\Booking\Repositories\Eloquent;

use App\Booking\Filters\EloquentBookingDateRangeFilter;
use App\Booking\Filters\EloquentBookingOfferingIdFilter;
use App\Booking\Filters\EloquentBookingPaymentStatusFilter;
use App\Booking\Filters\EloquentBookingStatusFilter;
use App\Shared\Filters\SortFilter;
use App\Booking\Models\Booking;
use App\Booking\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Pipeline\Pipeline;

class BookingEloquentRepository implements BookingRepositoryInterface
{
    public function create(array $data): array
    {
        $booking = Booking::create($data);

        return $booking->load(['timeSlot', 'offering', 'user'])->toArray();
    }

    public function findById(int $id): ?array
    {
        $booking = Booking::with(['timeSlot.offeringDay', 'offering', 'user'])->find($id);

        return $booking ? $booking->toArray() : null;
    }

    public function findByUser(int $userId, array $filters = []): array
    {
        $query = Booking::with(['timeSlot.offeringDay', 'offering'])
            ->where('user_id', $userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        return $bookings->toArray();
    }

    public function findByOffering(int $offeringId, array $filters = []): array
    {
        $query = Booking::with(['timeSlot.offeringDay', 'user'])
            ->where('offering_id', $offeringId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        return $bookings->toArray();
    }

    public function countConfirmedForSlot(int $offeringTimeSlotId): int
    {
        return Booking::where('offering_time_slot_id', $offeringTimeSlotId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();
    }

    public function update(int $id, array $data): void
    {
        Booking::where('id', $id)->update($data);
    }

    public function cancel(int $id, ?string $reason = null): void
    {
        $data = [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ];

        if ($reason) {
            $data['cancellation_reason'] = $reason;
        }

        Booking::where('id', $id)->update($data);
    }

    public function listAllForAgencyWithFilters(int $agencyUserId, array $filters, int $page, int $pageSize): array
    {
        // Build base query with offering relationship to filter by agency
        $query = app(Pipeline::class)
            ->send(
                Booking::query()
                    ->with(['timeSlot.offeringDay', 'offering', 'user'])
                    ->whereHas('offering', function ($q) use ($agencyUserId) {
                        $q->where('user_id', $agencyUserId);
                    })
            )
            ->through([
                new EloquentBookingOfferingIdFilter($filters['offering_id'] ?? null),
                new EloquentBookingStatusFilter($filters['status'] ?? null),
                new EloquentBookingPaymentStatusFilter($filters['payment_status'] ?? null),
                new EloquentBookingDateRangeFilter(
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null
                ),
                new SortFilter(
                    $filters['sort_by'] ?? 'created_at',
                    $filters['sort_direction'] ?? 'desc',
                    ['created_at', 'confirmed_at', 'total_price', 'status']
                ),
            ])
            ->thenReturn();

        $bookings = $query->paginate($pageSize, ['*'], 'page', $page);

        return [
            'data' => $bookings->items(),
            'current_page' => $bookings->currentPage(),
            'per_page' => $bookings->perPage(),
            'total' => $bookings->total(),
            'last_page' => $bookings->lastPage(),
        ];
    }

    public function listAllForCustomerWithFilters(int $userId, array $filters, int $page, int $pageSize): array
    {
        // Build base query filtered by customer user_id
        $query = app(Pipeline::class)
            ->send(
                Booking::query()
                    ->with(['timeSlot.offeringDay', 'offering'])
                    ->where('user_id', $userId)
            )
            ->through([
                new EloquentBookingOfferingIdFilter($filters['offering_id'] ?? null),
                new EloquentBookingStatusFilter($filters['status'] ?? null),
                new EloquentBookingPaymentStatusFilter($filters['payment_status'] ?? null),
                new EloquentBookingDateRangeFilter(
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null
                ),
                new SortFilter(
                    $filters['sort_by'] ?? 'created_at',
                    $filters['sort_direction'] ?? 'desc',
                    ['created_at', 'confirmed_at', 'total_price', 'status']
                ),
            ])
            ->thenReturn();

        $bookings = $query->paginate($pageSize, ['*'], 'page', $page);

        return [
            'data' => $bookings->items(),
            'current_page' => $bookings->currentPage(),
            'per_page' => $bookings->perPage(),
            'total' => $bookings->total(),
            'last_page' => $bookings->lastPage(),
        ];
    }
}
