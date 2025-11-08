<?php

namespace App\Services;

use App\Repositories\MySql\Offering\OfferingEloquentRepository;

class OfferingService
{
    public function __construct(protected OfferingEloquentRepository $offeringRepository)
    {
    }

    public function createOffering(array $data): array
    {
        return $this->offeringRepository->create($data);
    }
}
