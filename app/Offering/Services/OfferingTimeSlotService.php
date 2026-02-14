<?php

namespace App\Offering\Services;

use App\Offering\Exceptions\OfferingNotFoundException;
use App\Offering\Exceptions\OfferingDayNotFoundException;
use App\Offering\Exceptions\OfferingTimeSlotNotFoundException;
use App\Auth\Exceptions\AuthenticationException;
use App\Offering\Repositories\Contracts\OfferingDayRepositoryInterface;
use App\Offering\Repositories\Contracts\OfferingRepositoryInterface;
use App\Offering\Repositories\Contracts\OfferingTimeSlotRepositoryInterface;

class OfferingTimeSlotService
{
    public function __construct(
        protected OfferingTimeSlotRepositoryInterface $offeringTimeSlotRepository,
        protected OfferingDayRepositoryInterface $offeringDayRepository,
        protected OfferingRepositoryInterface $offeringRepository,
    ) {}

    public function createTimeSlot(array $data): array
    {
        $offeringDay = $this->offeringDayRepository->findById($data['offering_day_id']);

        if (! $offeringDay) {
            throw new OfferingDayNotFoundException();
        }

        $offering = $this->offeringRepository->findWhere(['id' => $offeringDay['offering_id']]);

        if (empty($offering)) {
            throw new OfferingNotFoundException();
        }

        if (auth()->user()->id != $offering['user_id']) {
            throw new AuthenticationException();
        }

        $data['offering_id'] = $offeringDay['offering_id'];

        return $this->offeringTimeSlotRepository->create($data);
    }

    public function bulkCreateTimeSlots(int $offeringDayId, array $slots): array
    {
        $offeringDay = $this->offeringDayRepository->findById($offeringDayId);

        if (! $offeringDay) {
            throw new OfferingDayNotFoundException();
        }

        $offering = $this->offeringRepository->findWhere(['id' => $offeringDay['offering_id']]);

        if (empty($offering)) {
            throw new OfferingNotFoundException();
        }

        if (auth()->user()->id != $offering['user_id']) {
            throw new AuthenticationException();
        }

        $slotsToCreate = array_map(function ($slot) use ($offeringDayId, $offeringDay) {
            return [
                'offering_day_id' => $offeringDayId,
                'offering_id' => $offeringDay['offering_id'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'capacity' => $slot['capacity'] ?? 1,
                'price_override' => $slot['price_override'] ?? null,
            ];
        }, $slots);

        return $this->offeringTimeSlotRepository->bulkCreate($slotsToCreate);
    }

    public function getTimeSlots(int $offeringDayId): array
    {
        $offeringDay = $this->offeringDayRepository->findById($offeringDayId);

        if (! $offeringDay) {
            throw new OfferingDayNotFoundException();
        }

        $offering = $this->offeringRepository->findWhere(['id' => $offeringDay['offering_id']]);

        if (empty($offering)) {
            throw new OfferingNotFoundException();
        }

        if (auth()->user()->id != $offering['user_id']) {
            throw new AuthenticationException();
        }

        return $this->offeringTimeSlotRepository->findByOfferingDay($offeringDayId);
    }

    public function updateTimeSlot(int $id, array $data): void
    {
        $timeSlot = $this->offeringTimeSlotRepository->findById($id);

        if (! $timeSlot) {
            throw new OfferingTimeSlotNotFoundException();
        }

        $offering = $this->offeringRepository->findWhere(['id' => $timeSlot['offering_id']]);

        if (empty($offering)) {
            throw new OfferingNotFoundException();
        }

        if (auth()->user()->id != $offering['user_id']) {
            throw new AuthenticationException();
        }

        $this->offeringTimeSlotRepository->update($id, $data);
    }

    public function deleteTimeSlot(int $id): void
    {
        $timeSlot = $this->offeringTimeSlotRepository->findById($id);

        if (! $timeSlot) {
            throw new OfferingTimeSlotNotFoundException();
        }

        $offering = $this->offeringRepository->findWhere(['id' => $timeSlot['offering_id']]);

        if (empty($offering)) {
            throw new OfferingNotFoundException();
        }

        if (auth()->user()->id != $offering['user_id']) {
            throw new AuthenticationException();
        }

        $this->offeringTimeSlotRepository->delete($id);
    }
}
