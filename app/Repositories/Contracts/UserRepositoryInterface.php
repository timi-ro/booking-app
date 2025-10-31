<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function insert(array $data) : User;

    public function findWhere(array $where) : array;

    public function update(array $data) : void;

    public function delete(int $id) : void;
}
