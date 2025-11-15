<?php

namespace App\Http\Controllers\Agency;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\CreateMediaRequest;
use App\Services\MediaEntityResolver;
use App\Services\MediaService;
use App\Services\OfferingService;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService        $mediaService,
        protected OfferingService     $offeringService,
        protected MediaEntityResolver $mediaEntityResolver,
    )
    {
    }

    public function upload(CreateMediaRequest $request)
    {
        $validated = $request->validated();

        $this->mediaEntityResolver->validateOrFail(
            $validated['entity'],
            $validated['entity_id']
        );

        $media = $this->mediaService->upload($validated);

        return ResponseHelper::generateResponse([$media]);
    }
}
