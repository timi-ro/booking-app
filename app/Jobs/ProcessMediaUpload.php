<?php

namespace App\Jobs;

use App\Constants\MediaFilePaths;
use App\Drivers\Contracts\StorageDriverInterface;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $userId,
        public string $entity,
        public int $entityId,
        public string $tempFilePath,
        public string $originalFileName,
        public string $mimeType,
        public int $fileSize,
        public string $mediableType,
        public string $collection,
    ) {
    }

    public function handle(
        StorageDriverInterface $storageDriver,
        MediaRepositoryInterface $mediaRepository
    ): void {
        try {
            // Verify temp file exists
            if (!file_exists($this->tempFilePath)) {
                throw new \Exception("Temporary file not found: {$this->tempFilePath}");
            }

            // 1. Read the temporary file
            $fileContents = file_get_contents($this->tempFilePath);

            // 2. Store the file in its final location using our path pattern
            $finalPath = $this->userId . MediaFilePaths::MEDIA_BASE_PATH . '/' . $this->entity;

            $path = $storageDriver->putFile(
                path: $finalPath,
                content: $fileContents
            );

            // 3. Create the media record
            $data = [
                'mediable_type' => $this->mediableType,
                'mediable_id' => $this->entityId,
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $this->mimeType,
                'size' => $this->fileSize,
                'collection' => $this->collection,
            ];

            $mediaRepository->create($data);

            // 4. Clean up temporary file
            if (file_exists($this->tempFilePath)) {
                unlink($this->tempFilePath);
            }

            Log::info('Media uploaded successfully', [
                'path' => $path,
                'entity' => $this->entity,
                'entity_id' => $this->entityId
            ]);

        } catch (\Exception $e) {
            Log::error('Media upload failed', [
                'error' => $e->getMessage(),
                'temp_path' => $this->tempFilePath,
                'entity' => $this->entity,
                'entity_id' => $this->entityId
            ]);

            // Clean up temp file even on failure
            if (file_exists($this->tempFilePath)) {
                unlink($this->tempFilePath);
            }

            // Re-throw to mark job as failed
            throw $e;
        }
    }
}
