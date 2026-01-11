<?php

namespace App\Repositories\Contracts;

interface OfferingRepositoryInterface
{
    public function create(array $data): array;

    public function index(int $userId, int $page, int $pageSize): array;

    public function listAllWithFilters(array $filters, int $page, int $pageSize): array;

    public function findByIdWithRelations(int $id): ?array;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;

    public function findWhere(array $where): array;
}
