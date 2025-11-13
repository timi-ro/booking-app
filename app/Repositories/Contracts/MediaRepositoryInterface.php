<?php

namespace App\Repositories\Contracts;

interface MediaRepositoryInterface
{
    public function create(array $data) : array;
}
