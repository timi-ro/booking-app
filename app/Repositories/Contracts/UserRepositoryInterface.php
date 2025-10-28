<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function insert(array $data) : array;

    public function findWhere(array $where) : array;

    public function update(array $data) : void;

    public function delete(int $id) : void;
}
