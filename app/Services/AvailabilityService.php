<?php

namespace App\Services;

use App\Exceptions\Offering\OfferingNotFoundException;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\OfferingRepositoryInterface;

class AvailabilityService
{
    public function __construct(
        protected AvailabilityRepositoryInterface $availabilityRepository,
        protected OfferingRepositoryInterface $offeringRepository,
    ) {
    }

    public function createAvailability(array $data): array
    {
        $offering = $this->offeringRepository->findWhere(['id' => $data['offering_id']]);

        if (empty($offering) || auth()->user()->id != $offering['user_id']) {
            throw new OfferingNotFoundException();
        }

        return $this->availabilityRepository->create($data);
    }
}
