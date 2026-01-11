<?php

namespace App\Http\Controllers\Agency;

use App\Exceptions\Media\MediableNotFoundException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\CreateMediaRequest;
use App\Services\MediaService;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService,
    ) {}

    public function upload(CreateMediaRequest $request)
    {
        $validated = $request->validated();

        $this->mediaService->validateMediable($validated['entity'], $validated['entity_id']);

        $uuid = $this->mediaService->upload($validated);

        return ResponseHelper::generateResponse(['uuid' => $uuid]);
    }

    public function validate(string $uuid)
    {
        $media = $this->mediaService->getByUuid($uuid);

        if (! $media) {
            throw new MediableNotFoundException();
        }

        return ResponseHelper::generateResponse([
            'uuid' => $media['uuid'],
            'status' => $media['status'],
            'original_filename' => $media['original_filename'],
            'mime_type' => $media['mime_type'],
            'size' => $media['size'],
        ]);
    }

    public function delete(string $uuid)
    {
        $media = $this->mediaService->getByUuid($uuid);

        if (! $media) {
            throw new MediableNotFoundException();
        }

        $this->mediaService->deleteByUuid($uuid);

        return ResponseHelper::generateResponse(['message' => 'Media deleted successfully']);
    }
}
