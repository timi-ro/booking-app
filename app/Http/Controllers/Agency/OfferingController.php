<?php

namespace App\Http\Controllers\Agency;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Offering\CreateOfferingRequest;
use App\Services\OfferingService;
use Symfony\Component\HttpFoundation\Response;

class OfferingController extends Controller
{
    public function __construct(protected OfferingService $offeringService){
    }

    public function create(CreateOfferingRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');
        }

        if ($request->hasFile('video')) {
            $validated['video'] = $request->file('video');
        }

        $offering = $this->offeringService->createOffering($validated);

        return ResponseHelper::generateResponse($offering, Response::HTTP_CREATED);
    }
}
