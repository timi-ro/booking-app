<?php

namespace App\Repositories\MySql\User;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserEloquentRepository implements UserRepositoryInterface
{
    public function insert(array $data): array
    {
       return User::create($data)->toArray();
    }

    public function findWhere(array $where): array
    {
        return [];
    }

    public function update(array $data): void
    {

    }

    public function delete(int $id): void
    {

    }
}
