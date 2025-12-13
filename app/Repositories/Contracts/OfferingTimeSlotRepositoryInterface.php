<?php

namespace App\Repositories\Contracts;

interface OfferingTimeSlotRepositoryInterface
{
    public function create(array $data): array;

    public function bulkCreate(array $slots): array;

    public function upsert(array $data): array;

    public function findById(int $id): ?array;

    public function findByOfferingDay(int $offeringDayId): array;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;

    public function incrementBookedCount(int $id): void;

    public function decrementBookedCount(int $id): void;
}
