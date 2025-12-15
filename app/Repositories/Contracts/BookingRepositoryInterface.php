<?php

namespace App\Repositories\Contracts;

interface BookingRepositoryInterface
{
    public function create(array $data): array;

    public function findById(int $id): ?array;

    public function findByUser(int $userId, array $filters = []): array;

    public function findByOffering(int $offeringId, array $filters = []): array;

    public function listAllForAgencyWithFilters(int $agencyUserId, array $filters, int $page, int $pageSize): array;

    public function listAllForCustomerWithFilters(int $userId, array $filters, int $page, int $pageSize): array;

    public function countConfirmedForSlot(int $offeringTimeSlotId): int;

    public function update(int $id, array $data): void;

    public function cancel(int $id, ?string $reason = null): void;
}
