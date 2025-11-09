<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Offering\CreateOfferingRequest;
use App\Services\OfferingService;

class OfferingController extends Controller
{
    public function __construct(protected OfferingService $offeringService){
    }

    public function create(CreateOfferingRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');//->store('offerings/images', 'public');
        }

        if ($request->hasFile('video')) {
            $validated['video'] = $request->file('video');//->store('offerings/videos', 'public');
        }

        $offering = $this->offeringService->createOffering($validated);

        return response()->json($offering);
    }
}
