<?php

namespace App\Http\Controllers\Agency;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\CreateMediaRequest;
use App\Services\MediaService;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService,
    )
    {
    }

    public function upload(CreateMediaRequest $request)
    {
        $validated = $request->validated();

        $this->mediaService->validateMediable($validated['entity'], $validated['entity_id']);

        $media = $this->mediaService->upload($validated);


        //TODO: only return uuid
        return ResponseHelper::generateResponse([$media]);
    }
}
