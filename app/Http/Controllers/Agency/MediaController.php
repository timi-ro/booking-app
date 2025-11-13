<?php

namespace App\Http\Controllers\Agency;

use App\Exceptions\Media\MediableNotFoundException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\CreateMediaRequest;
use App\Services\MediaService;
use App\Services\OfferingService;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService    $mediaService,
        protected OfferingService $offeringService,
    )
    {
    }

    public function upload(CreateMediaRequest $request)
    {
        $validated = $request->validated();

        //TODO: separate this concern
        $entityExistence = match ($validated['entity']) {
            'offering' => $this->offeringService->exist($validated['entity_id'])
        };

        if(!$entityExistence) {
            throw new MediableNotFoundException();
        }

        $media = $this->mediaService->upload($validated);

        return ResponseHelper::generateResponse([$media]);
    }
}
