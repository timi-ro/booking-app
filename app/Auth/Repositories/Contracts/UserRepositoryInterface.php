<?php

namespace App\Auth\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function create(array $data): array;
}
