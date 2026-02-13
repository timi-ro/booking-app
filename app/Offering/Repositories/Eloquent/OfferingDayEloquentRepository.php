<?php

namespace App\Offering\Repositories\Eloquent;

use App\Offering\Models\OfferingDay;
use App\Offering\Repositories\Contracts\OfferingDayRepositoryInterface;

class OfferingDayEloquentRepository implements OfferingDayRepositoryInterface
{
    public function create(array $data): array
    {
        $offeringDay = OfferingDay::create([
            'offering_id' => $data['offering_id'],
            'date' => $data['date'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $offeringDay->toArray();
    }

    public function findById(int $id): ?array
    {
        $offeringDay = OfferingDay::with('timeSlots')->find($id);

        return $offeringDay ? $offeringDay->toArray() : null;
    }

    public function findByOffering(int $offeringId): array
    {
        $offeringDays = OfferingDay::with('timeSlots')
            ->where('offering_id', $offeringId)
            ->orderBy('date')
            ->get();

        return $offeringDays->toArray();
    }

    public function update(int $id, array $data): void
    {
        OfferingDay::where('id', $id)->update($data);
    }

    public function delete(int $id): void
    {
        OfferingDay::destroy($id);
    }
}
