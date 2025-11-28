<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Offering\BrowseOfferingsRequest;
use App\Services\OfferingService;

class OfferingController extends Controller
{
    public function __construct(protected OfferingService $offeringService)
    {
    }

    public function index(BrowseOfferingsRequest $request)
    {
        $validated = $request->validated();

        $offerings = $this->offeringService->browseOfferings($validated);

        return ResponseHelper::generateResponse($offerings);
    }

    public function show(int $id)
    {
        $offering = $this->offeringService->getOfferingDetails($id);

        if (!$offering) {
            return ResponseHelper::generateResponse(
                [],
                'Offering not found',
                404
            );
        }

        return ResponseHelper::generateResponse($offering);
    }
}
