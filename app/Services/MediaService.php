<?php

namespace App\Services;

use App\Constants\MediaEntities;
use App\Constants\MediaFilePaths;
use App\Drivers\Contracts\QueueDriverInterface;
use App\Drivers\Contracts\StorageDriverInterface;
use App\Exceptions\Media\InvalidMediaEntityException;
use App\Exceptions\Media\MediableNotFoundException;
use App\Jobs\ProcessMediaUpload;
use App\Models\Offering;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Contracts\OfferingRepositoryInterface;
use Illuminate\Http\UploadedFile;

class MediaService
{
    public function __construct(
        protected MediaRepositoryInterface $mediaRepository,
        protected StorageDriverInterface $storageDriver,
        protected QueueDriverInterface $queueDriver,
        protected OfferingRepositoryInterface $offeringRepository,
    ) {
    }

    public function upload(array $data): array
    {
        $mediableType = $this->getMediableType($data['entity']);

        if (!$mediableType) {
            throw new InvalidMediaEntityException();
        }

        // TODO: Try this way as well, no need for temp but might have some cons (study pros and cons)
        // $encoded_file = base64_encode(file_get_contents($data['file']));

        $file = $data['file'];
        $tempPath = $this->storeTempFile($file);
        $fullTempPath = $this->storageDriver->getPath($tempPath);
        $mimeType = $file->getClientMimeType();

        ProcessMediaUpload::dispatch(
            userId: auth()->user()->id,
            entity: $data['entity'],
            entityId: $data['entity_id'],
            tempFilePath: $fullTempPath,
            originalFileName: $data['file']->getClientOriginalName(),
            mimeType: $mimeType,
            fileSize: $data['file']->getSize(),
            mediableType: $mediableType,
            collection: $data['collection'],
        );

        return [
            'status' => 'processing',
            'message' => 'Media upload is being processed',
            'entity' => $data['entity'],
            'entity_id' => $data['entity_id'],
        ];
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

    protected function storeTempFile(UploadedFile $file): string
    {
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $tempPath = MediaFilePaths::TEMP_PATH . '/' . $filename;

        $this->storageDriver->putFile($tempPath, file_get_contents($file));

        return $tempPath;
    }

    protected function getMediableType(string $entityName): ?string
    {
        return match ($entityName) {
            MediaEntities::MEDIA_OFFERING => Offering::class,
            default => null,
        };
    }
}
