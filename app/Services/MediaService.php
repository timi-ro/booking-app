<?php

namespace App\Services;

use App\Constants\MediaCollections;
use App\Constants\MediaFilePaths;
use App\Drivers\Contracts\QueueDriverInterface;
use App\Drivers\Contracts\StorageDriverInterface;
use App\Exceptions\Media\InvalidMediaEntityException;
use App\Models\Offering;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Http\UploadedFile;

class MediaService
{
    public function __construct(
        protected MediaRepositoryInterface $mediaRepository,
        protected StorageDriverInterface $storageDriver,
        protected QueueDriverInterface $queueDriver,
    ) {
    }

    public function upload(array $data): array
    {
        $mediableType = $this->getMediableType($data['entity']);

        if (!$mediableType) {
            throw new InvalidMediaEntityException();
        }

        $this->queueMediaProcessing($data['file'], $mediableType, $data['entity'], $data['entity_id'], $data['collection']);

        return [
            'status' => 'processing',
            'message' => 'Media upload is being processed',
            'entity' => $data['entity'],
            'entity_id' => $data['entity_id'],
        ];
    }

    protected function queueMediaProcessing(UploadedFile $file, string $mediableType, string $entity, int $entityId, string $collection): void
    {
        $tempPath = $this->storeTempFile($file);
        $fullTempPath = $this->storageDriver->getPath($tempPath);
        $mimeType = $file->getClientMimeType();

        $this->queueDriver->dispatchMediaProcessing([
            'user_id' => (string) auth()->user()->id,
            'entity' => $entity,
            'entity_id' => $entityId,
            'temp_file_path' => $fullTempPath,
            'original_file_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'mediable_type' => $mediableType,
            'collection' => $collection,
        ]);
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
            'offering' => Offering::class,
            default => null,
        };
    }
}
