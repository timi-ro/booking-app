<?php

namespace App\Services;

use App\Constants\OfferingFilePaths;
use App\Drivers\Contracts\StorageDriverInterface;
use App\Exceptions\Offering\OfferingNotFoundException;
use App\Repositories\Contracts\OfferingRepositoryInterface;

class OfferingService
{
    public function __construct(
        protected OfferingRepositoryInterface $offeringRepository,
        protected StorageDriverInterface $storageDriver,
    )
    {
    }

    public function createOffering(array $data): array
    {
        $userId = auth()->user()->id;
        $data['user_id'] = $userId;

        return $this->offeringRepository->create($data);
    }

    public function listOfferings(int $userId, array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 15;

        return $this->offeringRepository->index($userId, $page, $pageSize);
    }

    public function updateOffering(int $id, array $data): void
    {
        $offering = $this->offeringRepository->findWhere(['id' => $id]);

        if (empty($offering) || auth()->user()->id != $offering['user_id']) {
            throw new OfferingNotFoundException();
        }

        $this->offeringRepository->update($id, $data);
    }

    public function deleteOffering(int $id):void
    {
        $offering = $this->offeringRepository->findWhere(['id' => $id]);

        if (empty($offering) || auth()->user()->id != $offering['user_id']) {
            throw new OfferingNotFoundException();
        }

        collect(['video', 'image'])
            ->map(fn($field) => $offering[$field] ?? null)
            ->filter()
            ->each(fn($path) => $this->storageDriver->deleteFile($path));

        $this->offeringRepository->delete($id);
    }

    public function exist(int $id): bool
    {
        $offering  = $this->offeringRepository->findWhere(['id' => $id]);
        return (bool)$offering;
    }
}
