<?php

namespace App\Services;

use App\Drivers\Contracts\StorageDriverInterface;
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
        //TODO:: make paths constant
        $videoPath = $this->storageDriver->putFile("{$userId}/offerings/videos", $data['video']);
        $imagePath = $this->storageDriver->putFile("{$userId}/offerings/images", $data['image']);
        $data['image'] =  $imagePath;
        $data['video'] =  $videoPath;

        return $this->offeringRepository->create($data);
    }
}
