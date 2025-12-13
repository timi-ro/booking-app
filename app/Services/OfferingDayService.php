<?php

namespace App\Services;

use App\Exceptions\Offering\OfferingNotFoundException;
use App\Exceptions\OfferingDay\OfferingDayNotFoundException;
use App\Exceptions\UnauthorizedAccessException;
use App\Repositories\Contracts\OfferingDayRepositoryInterface;
use App\Repositories\Contracts\OfferingRepositoryInterface;

class OfferingDayService
{
    public function __construct(
        protected OfferingDayRepositoryInterface $offeringDayRepository,
        protected OfferingRepositoryInterface $offeringRepository,
    ) {
    }

    public function createOfferingDay(array $data): array
    {
        $offering = $this->offeringRepository->findWhere(['id' => $data['offering_id']]);

        if (empty($offering) || auth()->user()->id != $offering['user_id']) {
            throw new OfferingNotFoundException();
        }

        return $this->offeringDayRepository->create($data);
    }

    public function getOfferingDays(int $offeringId): array
    {
        $offering = $this->offeringRepository->findWhere(['id' => $offeringId]);

        if (empty($offering) || auth()->user()->id != $offering['user_id']) {
            throw new OfferingNotFoundException();
        }

        return $this->offeringDayRepository->findByOffering($offeringId);
    }

    public function updateOfferingDay(int $id, array $data): void
    {
        $offeringDay = $this->offeringDayRepository->findById($id);

        if (!$offeringDay) {
            throw new OfferingDayNotFoundException();
        }

        $offering = $this->offeringRepository->findWhere(['id' => $offeringDay['offering_id']]);

        if (empty($offering)) {
            throw new OfferingNotFoundException();
        }

        if (auth()->user()->id != $offering['user_id']) {
            throw new UnauthorizedAccessException();
        }

        $this->offeringDayRepository->update($id, $data);
    }

    public function deleteOfferingDay(int $id): void
    {
        $offeringDay = $this->offeringDayRepository->findById($id);

        if (!$offeringDay) {
            throw new OfferingDayNotFoundException();
        }

        $offering = $this->offeringRepository->findWhere(['id' => $offeringDay['offering_id']]);

        if (empty($offering)) {
            throw new OfferingNotFoundException();
        }

        if (auth()->user()->id != $offering['user_id']) {
            throw new UnauthorizedAccessException();
        }

        $this->offeringDayRepository->delete($id);
    }
}
