<?php

namespace App\Http\Controllers\Agency;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Offering\CreateOfferingRequest;
use App\Http\Requests\Offering\ListOfferingRequest;
use App\Http\Requests\Offering\UpdateOfferingRequest;
use App\Models\Offering;
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

        $validated['user_id'] = auth()->user()->id;
        $offering = $this->offeringService->createOffering($validated);

        return ResponseHelper::generateResponse($offering, Response::HTTP_CREATED);
    }

    public function index(ListOfferingRequest $request)
    {
        $validated = $request->validated();

        $userId = auth()->user()->id;
        $offerings = $this->offeringService->listOfferings($userId, $validated);

        return ResponseHelper::generateResponse($offerings);
    }

    public function update(UpdateOfferingRequest $request, Offering $offering)
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();

        $updatedOffering = $this->offeringService->updateOffering($offering, $validated);

        return ResponseHelper::generateResponse($updatedOffering);
    }

    public function delete(Offering $offering)
    {
        $this->offeringService->deleteOffering($offering);
        return ResponseHelper::generateResponse([], Response::HTTP_NO_CONTENT);
    }
}
