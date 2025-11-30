<?php

namespace App\Repositories\MySql\Availability;

use App\Models\Availability;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;

class AvailabilityEloquentRepository implements AvailabilityRepositoryInterface
{
    public function create(array $data): array
    {
        $availability = Availability::create([
            'offering_id' => $data['offering_id'],
            'details' => $data['details'],
        ]);

        return $availability->toArray();
    }

    public function findWhere(array $where): array
    {
        $availability = Availability::where($where)->first();

        return $availability ? $availability->toArray() : [];
    }
}
