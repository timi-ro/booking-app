<?php

namespace App\Http\Controllers\Agency;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Offering\CreateOfferingRequest;
use App\Http\Requests\Offering\ListOfferingRequest;
use App\Http\Requests\Offering\UpdateOfferingRequest;
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

    public function index(ListOfferingRequest $request)
    {
        $validated = $request->validated();

        $userId = auth()->user()->id;
        $offerings = $this->offeringService->listOfferings($userId, $validated);

        // TODO: use laravel api resource align with helper
        // TODO: handle dates
        return ResponseHelper::generateResponse($offerings);
    }

    public function update(UpdateOfferingRequest $request, int $id)
    {
        $validated = $request->validated();

        $validated['user_id'] = $request->user()->id;
        $this->offeringService->updateOffering($id, $validated);

        return ResponseHelper::generateResponse([], Response::HTTP_NO_CONTENT);
    }

    public function delete(int $id)
    {
        $this->offeringService->deleteOffering($id);
        return ResponseHelper::generateResponse([], Response::HTTP_NO_CONTENT);
    }
}
