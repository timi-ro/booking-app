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
        if ($request->hasFile('image')) {
            $request['image'] = $request->file('image')->store('offerings/images', 'public');
        }
        if ($request->hasFile('video')) {
            $request['video'] = $request->file('video')->store('offerings/videos', 'public');
        }

        $validated = $request->validated();

        $offering = $this->offeringService->createOffering($validated);

        return response()->json($offering);

    }
}
