<?php

namespace App\Repositories\Contracts;

use App\Models\Offering;

interface OfferingRepositoryInterface
{
    public function create(array $data) : array;

    public function index(int $userId, int $page, int $pageSize): array;

    public function update(Offering $offering, array $data): array;

    public function delete(Offering $offering): void;
}
