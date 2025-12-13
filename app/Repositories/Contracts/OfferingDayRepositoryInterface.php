<?php

namespace App\Repositories\Contracts;

interface OfferingDayRepositoryInterface
{
    public function create(array $data): array;

    public function findById(int $id): ?array;

    public function findByOffering(int $offeringId): array;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;
}
