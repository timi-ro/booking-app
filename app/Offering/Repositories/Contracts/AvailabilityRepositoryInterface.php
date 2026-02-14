<?php

namespace App\Offering\Repositories\Contracts;

interface AvailabilityRepositoryInterface
{
    public function create(array $data): array;

    public function findWhere(array $where): array;
}
