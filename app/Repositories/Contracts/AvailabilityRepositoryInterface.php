<?php

namespace App\Repositories\Contracts;

interface AvailabilityRepositoryInterface
{
    public function create(array $data): array;

    public function findWhere(array $where): array;
}
