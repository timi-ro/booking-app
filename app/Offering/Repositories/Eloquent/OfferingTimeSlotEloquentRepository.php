<?php

namespace App\Offering\Repositories\Eloquent;

use App\Offering\Models\OfferingTimeSlot;
use App\Offering\Repositories\Contracts\OfferingTimeSlotRepositoryInterface;

class OfferingTimeSlotEloquentRepository implements OfferingTimeSlotRepositoryInterface
{
    public function create(array $data): array
    {
        $timeSlot = OfferingTimeSlot::create([
            'offering_day_id' => $data['offering_day_id'],
            'offering_id' => $data['offering_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'capacity' => $data['capacity'] ?? 1,
            'price_override' => $data['price_override'] ?? null,
        ]);

        return $timeSlot->toArray();
    }

    public function bulkCreate(array $slots): array
    {
        $created = [];

        foreach ($slots as $slotData) {
            $created[] = $this->upsert($slotData);
        }

        return $created;
    }

    public function upsert(array $data): array
    {
        // Try to find existing slot by offering_day_id, start_time, and end_time
        $existingSlot = OfferingTimeSlot::where('offering_day_id', $data['offering_day_id'])
            ->where('start_time', $data['start_time'])
            ->where('end_time', $data['end_time'])
            ->first();

        if ($existingSlot) {
            // Update existing slot (but don't reset booked_count)
            $existingSlot->update([
                'capacity' => $data['capacity'] ?? $existingSlot->capacity,
                'price_override' => $data['price_override'] ?? $existingSlot->price_override,
            ]);

            return $existingSlot->fresh()->toArray();
        }

        // Create new slot if it doesn't exist
        return $this->create($data);
    }

    public function findById(int $id): ?array
    {
        $timeSlot = OfferingTimeSlot::find($id);

        return $timeSlot ? $timeSlot->toArray() : null;
    }

    public function findByOfferingDay(int $offeringDayId): array
    {
        $timeSlots = OfferingTimeSlot::where('offering_day_id', $offeringDayId)
            ->orderBy('start_time')
            ->get();

        return $timeSlots->toArray();
    }

    public function update(int $id, array $data): void
    {
        OfferingTimeSlot::where('id', $id)->update($data);
    }

    public function delete(int $id): void
    {
        OfferingTimeSlot::destroy($id);
    }

    public function incrementBookedCount(int $id): void
    {
        OfferingTimeSlot::where('id', $id)->increment('booked_count');
    }

    public function decrementBookedCount(int $id): void
    {
        OfferingTimeSlot::where('id', $id)
            ->where('booked_count', '>', 0)
            ->decrement('booked_count');
    }
}
