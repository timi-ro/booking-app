<?php

namespace App\Repositories\Contracts;

interface MediaRepositoryInterface
{
    public function create(array $data) : array;

    public function update(int $id, array $data): void;

    public function findByUuid(string $uuid): ?array;

    public function existsByMediableAndCollection(string $mediableType, int $mediableId, string $collection): bool;

    public function delete(int $id): void;
}
