<?php

namespace App\Offering\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Offering\BrowseOfferingsRequest;
use App\Offering\Exceptions\OfferingNotFoundException;
use App\Offering\Services\OfferingService;
use App\Shared\Helpers\ResponseHelper;

class OfferingController extends Controller
{
    public function __construct(protected OfferingService $offeringService) {}

    public function index(BrowseOfferingsRequest $request)
    {
        $validated = $request->validated();

        $offerings = $this->offeringService->browseOfferings($validated);

        return ResponseHelper::generateResponse($offerings);
    }

    public function show(int $id)
    {
        $offering = $this->offeringService->getOfferingDetails($id);

        if (! $offering) {
            throw new OfferingNotFoundException();
        }

        return ResponseHelper::generateResponse($offering);
    }
}
