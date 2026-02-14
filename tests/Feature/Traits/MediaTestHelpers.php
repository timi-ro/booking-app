<?php

namespace Tests\Feature\Traits;

use App\Shared\Drivers\Contracts\StorageDriverInterface;
use App\Media\Jobs\ProcessMediaUpload;
use App\Media\Models\Media;
use App\Media\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Http\UploadedFile;

trait MediaTestHelpers
{
    protected string $agencyMediaUrl = '/api/agency/media';

    protected array $uploadedMediaUuids = [];

    protected function createFakeImage(string $filename = 'test.jpg', int $sizeKB = 100): UploadedFile
    {
        return UploadedFile::fake()->image($filename, 800, 600)->size($sizeKB);
    }

    /**
     * Upload a real file from test fixtures.
     * Fixture files should be placed in tests/Fixtures/media/
     *
     * @param  string  $filename  The fixture filename (e.g., 'test-photo.jpg', 'test-video.mp4')
     */
    protected function uploadFixture(string $filename): UploadedFile
    {
        $path = base_path("tests/Fixtures/media/{$filename}");

        return new UploadedFile(
            path: $path,
            originalName: $filename,
            mimeType: mime_content_type($path) ?: 'application/octet-stream',
            error: null,
            test: true
        );
    }

    protected function assertMediaExists(string $uuid, ?string $disk = null): void
    {
        $disk = $disk ?? config('filesystems.default');

        $media = Media::where('uuid', $uuid)->first();
        $this->assertNotNull($media, "Media with UUID {$uuid} not found in database");

        if ($media->path) {
            $realPath = $this->getMediaRealPath($media->path, $disk);

            $this->assertFileExists(
                $realPath,
                "Media file does not exist at: {$realPath}"
            );

            $this->uploadedMediaUuids[] = $uuid;
        }
    }

    protected function assertMediaDeleted(string $uuid, ?string $disk = null): void
    {
        $disk = $disk ?? config('filesystems.default');

        $media = Media::withTrashed()->where('uuid', $uuid)->first();
        $this->assertNotNull($media, "Media with UUID {$uuid} not found in database");
        $this->assertNotNull($media->deleted_at, "Media with UUID {$uuid} is not soft deleted");

        if ($media->path) {
            $realPath = $this->getMediaRealPath($media->path, $disk);

            $this->assertFileDoesNotExist(
                $realPath,
                "Media file still exists at: {$realPath}"
            );
        }
    }

    protected function getMediaRealPath(string $relativePath, string $disk = 'local'): string
    {
        $diskRoot = config("filesystems.disks.{$disk}.root");

        return $diskRoot.'/'.ltrim($relativePath, '/');
    }

    protected function assertMediaStructure($response): void
    {
        $response->assertJsonStructure([
            'data' => [
                'uuid',
            ],
        ]);
    }

    protected function mediaUploadUrl(): string
    {
        return $this->agencyMediaUrl;
    }

    protected function mediaValidateUrl(string $uuid): string
    {
        return "{$this->agencyMediaUrl}/{$uuid}";
    }

    protected function mediaDeleteUrl(string $uuid): string
    {
        return "{$this->agencyMediaUrl}/{$uuid}";
    }

    protected function processMediaUpload(string $uuid, UploadedFile $file): void
    {
        $media = Media::where('uuid', $uuid)->first();

        if (! $media) {
            throw new \Exception("Media with UUID {$uuid} not found");
        }

        $fileContent = base64_encode(file_get_contents($file->getRealPath()));

        $job = new ProcessMediaUpload(
            userId: (string) $media->mediable->user_id,
            entity: $this->getEntityName($media->mediable_type),
            entityId: $media->mediable_id,
            mediaId: $media->id,
            encodedFileContent: $fileContent,
            originalFileName: $media->original_filename,
            mimeType: $media->mime_type,
            fileSize: $media->size,
            mediableType: $media->mediable_type,
            collection: $media->collection,
        );

        // Manually call handle() method with dependencies from the container
        // This bypasses Queue::fake() and actually executes the job
        $job->handle(
            app(StorageDriverInterface::class),
            app(MediaRepositoryInterface::class)
        );
    }

    protected function getEntityName(string $className): string
    {
        return strtolower(class_basename($className));
    }

    protected function cleanupMediaFiles(): void
    {
        $disk = config('filesystems.default');

        foreach ($this->uploadedMediaUuids as $uuid) {
            $media = Media::withTrashed()->where('uuid', $uuid)->first();

            if ($media && $media->path) {
                $realPath = $this->getMediaRealPath($media->path, $disk);

                if (file_exists($realPath)) {
                    unlink($realPath);
                }
            }
        }

        $this->uploadedMediaUuids = [];
    }
}
