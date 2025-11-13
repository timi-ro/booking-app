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
        $data = $this->prepareDataForUpdate($data);

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

    /**
     * @param array $data
     * @return array
     */
    private function prepareDataForUpdate(array $data): array
    {
        $userId = auth()->user()->id;
        if ($data['video']) {
            $videoPath = $this->storageDriver->putFile($userId . OfferingFilePaths::OFFERINGS_VIDEOS_PATH, $data['video']);
            $data['video'] = $videoPath;
        }

        if ($data['image']) {
            $imagePath = $this->storageDriver->putFile($userId . OfferingFilePaths::OFFERINGS_IMAGES_PATH, $data['image']);
            $data['image'] = $imagePath;
        }

        $data['user_id'] = $userId;

        return $data;
    }
}
