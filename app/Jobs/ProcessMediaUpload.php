<?php

namespace App\Jobs;

use App\Constants\MediaFilePaths;
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
        public readonly string $tempFilePath,
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
            //TODO: UPDATE state to processing
            $this->validateTempFile();

            $fileContents = $this->readTempFile();
            $finalPath = $this->generateFinalPath();
            $storedPath = $this->storeFile($storageDriver, $finalPath, $fileContents);
            //TODO: success status
            $this->createMediaRecord($mediaRepository, $storedPath);

        } catch (\Exception $e) {
            //TODO: failed status
            throw new MediaUploadFailedException($e->getMessage(), $e->getCode());
        } finally {
            $this->cleanupTempFile();
        }
    }

    /**
     * Validate that the temporary file exists and is readable.
     */
    protected function validateTempFile(): void
    {
        if (!file_exists($this->tempFilePath)) {
            throw new MediaUploadFailedException("Temporary file not found: {$this->tempFilePath}");
        }

        if (!is_readable($this->tempFilePath)) {
            throw new MediaUploadFailedException("Temporary file is not readable: {$this->tempFilePath}");
        }
    }

    /**
     * Read contents from the temporary file.
     */
    protected function readTempFile(): string
    {
        $contents = file_get_contents($this->tempFilePath);

        if ($contents === false) {
            throw new MediaUploadFailedException("Failed to read temporary file: {$this->tempFilePath}");
        }

        return $contents;
    }

    /**
     * Generate the final storage path for the media file.
     */
    protected function generateFinalPath(): string
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
    protected function storeFile(
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
     * Create the media database record.
     */
    protected function createMediaRecord(
        MediaRepositoryInterface $mediaRepository,
        string $storedPath
    ): void {
        $mediaRepository->create([
            'mediable_type' => $this->mediableType,
            'mediable_id' => $this->entityId,
            'disk' => config('media.default_disk', 'local'),
            'path' => $storedPath,
            'mime_type' => $this->mimeType,
            'size' => $this->fileSize,
            'collection' => $this->collection,
            'original_filename' => $this->originalFileName,
        ]);
    }

    /**
     * Clean up the temporary file.
     */
    protected function cleanupTempFile(): void
    {
        if (file_exists($this->tempFilePath)) {
            @unlink($this->tempFilePath);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Media upload job failed permanently after all retries', [
            'temp_path' => $this->tempFilePath,
            'entity' => $this->entity,
            'entity_id' => $this->entityId,
            'exception' => $exception->getMessage(),
        ]);

        // TODO: Optionally notify admins or trigger other cleanup
        // TODO: You could dispatch a notification here
    }
}
