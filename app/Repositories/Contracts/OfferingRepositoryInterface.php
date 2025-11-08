<?php

namespace App\Repositories\Contracts;

use App\Models\Offering;

interface OfferingRepositoryInterface
{
    public function create(array $data) : array;
}
