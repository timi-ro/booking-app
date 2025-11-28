<?php

namespace App\Services;

use App\Constants\MediaEntities;
use App\Constants\MediaStatuses;
use App\Drivers\Contracts\QueueDriverInterface;
use App\Drivers\Contracts\StorageDriverInterface;
use App\Exceptions\Media\DuplicateMediaException;
use App\Exceptions\Media\InvalidMediaEntityException;
use App\Exceptions\Media\MediableNotFoundException;
use App\Jobs\ProcessMediaUpload;
use App\Models\Offering;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Contracts\OfferingRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MediaService
{
    public function __construct(
        protected MediaRepositoryInterface $mediaRepository,
        protected StorageDriverInterface $storageDriver,
        protected QueueDriverInterface $queueDriver,
        protected OfferingRepositoryInterface $offeringRepository,
    ) {
    }

    public function upload(array $data): string
    {
        $mediableType = $this->getMediableType($data['entity']);

        if (!$mediableType) {
            throw new InvalidMediaEntityException();
        }

        // Validate that this entity doesn't already have media of this collection type
        $this->validateUniqueMedia($mediableType, $data['entity_id'], $data['collection']);

        $file = $data['file'];
        $encodedFile = base64_encode(file_get_contents($file->getRealPath()));
        $mimeType = $file->getClientMimeType();
        $uuid = (string) Str::uuid();

        // Create media record with uploading status
        $mediaRecord = $this->mediaRepository->create([
            'uuid' => $uuid,
            'mediable_type' => $mediableType,
            'mediable_id' => $data['entity_id'],
            'disk' => config('media.default_disk', 'local'),
            'path' => null,
            'mime_type' => $mimeType,
            'size' => $data['file']->getSize(),
            'collection' => $data['collection'],
            'original_filename' => $data['file']->getClientOriginalName(),
            'status' => MediaStatuses::MEDIA_STATUS_UPLOADING,
        ]);

        ProcessMediaUpload::dispatch(
            userId: auth()->user()->id,
            entity: $data['entity'],
            entityId: $data['entity_id'],
            mediaId: $mediaRecord['id'],
            encodedFileContent: $encodedFile,
            originalFileName: $data['file']->getClientOriginalName(),
            mimeType: $mimeType,
            fileSize: $data['file']->getSize(),
            mediableType: $mediableType,
            collection: $data['collection'],
        );

        return $uuid;
    }

    public function getByUuid(string $uuid): ?array
    {
        return $this->mediaRepository->findByUuid($uuid);
    }

    public function deleteByUuid(string $uuid): void
    {
        $media = $this->getByUuid($uuid);

        $this->mediaRepository->delete($media['id']);
    }

    public function validateUniqueMedia(string $mediableType, int $mediableId, string $collection): void
    {
        $exists = $this->mediaRepository->existsByMediableAndCollection(
            $mediableType,
            $mediableId,
            $collection
        );

        if ($exists) {
            throw new DuplicateMediaException("This entity already has a media file of type '{$collection}'.");
        }
    }

    public function validateMediable(string $entityType, int $entityId): void
    {
        $entity = match ($entityType) {
            MediaEntities::MEDIA_OFFERING => $this->offeringRepository->findWhere(['id' => $entityId]),
            default => throw new MediableNotFoundException("Unknown entity type: {$entityType}")
        };

        if (!$entity) {
            throw new MediableNotFoundException();
        }
    }

    protected function getMediableType(string $entityName): ?string
    {
        return match ($entityName) {
            MediaEntities::MEDIA_OFFERING => Offering::class,
            default => null,
        };
    }
}
