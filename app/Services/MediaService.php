<?php

namespace App\Services;

use App\Drivers\Contracts\StorageDriverInterface;
use App\Exceptions\Media\InvalidMediaEntityException;
use App\Models\Offering;
use App\Repositories\Contracts\MediaRepositoryInterface;

class MediaService
{
    public function __construct(
        protected StorageDriverInterface   $storageDriver,
        protected MediaRepositoryInterface $mediaRepository,
    )
    {
    }

    public function upload(array $data): array
    {
        //TODO: media should be upload on async mode
        $path = $this->storageDriver->putFile(
            path: auth()->user()->id . '/' . $data['entity'],
            content: $data['file'],
        );

        $mediable = $this->castEntityNameToMediableType($data['entity']);
        if (!$mediable) {
            throw new  InvalidMediaEntityException();
        }

        $data = [
            'mediable_type' => $mediable,
            'mediable_id' => $data['entity_id'],
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $data['file']->getClientMimeType(),
            'size' => $data['file']->getSize(),
            'collection' => $data['entity'],
        ];

        return $this->mediaRepository->create($data);
    }

    private function castEntityNameToMediableType(string $entityName): ?string
    {
        //TODO: improve this code
        return match ($entityName) {
            'offering' => Offering::class,
            default => null,
        };
    }
}
