<?php

namespace App\Jobs;

use App\Constants\MediaFilePaths;
use App\Constants\MediaStatuses;
use App\Drivers\Contracts\StorageDriverInterface;
use App\Exceptions\Media\MediaUploadFailedException;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMediaUpload implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    public function __construct(
        public readonly string $userId,
        public readonly string $entity,
        public readonly int $entityId,
        public readonly int $mediaId,
        public readonly string $encodedFileContent,
        public readonly string $originalFileName,
        public readonly string $mimeType,
        public readonly int $fileSize,
        public readonly string $mediableType,
        public readonly string $collection,
    ) {}

    public function handle(
        StorageDriverInterface $storageDriver,
        MediaRepositoryInterface $mediaRepository
    ): void {
        try {
            // Update status to processing
            $mediaRepository->update($this->mediaId, [
                'status' => MediaStatuses::MEDIA_STATUS_PROCESSING,
            ]);

            $fileContents = $this->decodeFileContent();
            $finalPath = $this->generateFinalPath();
            $storedPath = $this->storeFile($storageDriver, $finalPath, $fileContents);

            // Update media record with final path and completed status
            $mediaRepository->update($this->mediaId, [
                'path' => $storedPath,
                'status' => MediaStatuses::MEDIA_STATUS_COMPLETED,
            ]);

        } catch (\Exception $e) {
            // Update status to failed
            $mediaRepository->update($this->mediaId, [
                'status' => MediaStatuses::MEDIA_STATUS_FAILED,
            ]);

            throw new MediaUploadFailedException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Decode the base64 encoded file content.
     */
    private function decodeFileContent(): string
    {
        $contents = base64_decode($this->encodedFileContent, true);

        if ($contents === false) {
            throw new MediaUploadFailedException("Failed to decode file content");
        }

        return $contents;
    }

    /**
     * Generate the final storage path for the media file.
     */
    private function generateFinalPath(): string
    {
        return sprintf(
            '%s%s/%s',
            $this->userId,
            MediaFilePaths::MEDIA_BASE_PATH,
            $this->entity
        );
    }

    /**
     * Store the file using the storage driver.
     */
    private function storeFile(
        StorageDriverInterface $storageDriver,
        string $finalPath,
        string $fileContents
    ): string {
        return $storageDriver->putFile(
            path: $finalPath,
            content: $fileContents
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Update media status to failed
        app(MediaRepositoryInterface::class)->update($this->mediaId, [
            'status' => MediaStatuses::MEDIA_STATUS_FAILED,
        ]);

        Log::critical('Media upload job failed permanently after all retries', [
            'media_id' => $this->mediaId,
            'entity' => $this->entity,
            'entity_id' => $this->entityId,
            'exception' => $exception->getMessage(),
        ]);

        // TODO: Optionally notify admins or trigger other cleanup
        // TODO: You could dispatch a notification here
    }
}
