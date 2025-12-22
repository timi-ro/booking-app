<?php

namespace App\Http\Controllers\Agency;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\OfferingDay\CreateOfferingDayRequest;
use App\Http\Requests\OfferingDay\UpdateOfferingDayRequest;
use App\Services\OfferingDayService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OfferingDayController extends Controller
{
    public function __construct(
        protected OfferingDayService $offeringDayService,
    ) {
    }

    public function create(CreateOfferingDayRequest $request)
    {
        $validated = $request->validated();

        $offeringDay = $this->offeringDayService->createOfferingDay($validated);

        return ResponseHelper::generateResponse($offeringDay, Response::HTTP_CREATED);
    }

    public function index(Request $request)
    {
        $offeringId = $request->query('offering_id');

        if (!$offeringId) {
            return ResponseHelper::generateResponse(
                ['error' => 'offering_id query parameter is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $offeringDays = $this->offeringDayService->getOfferingDays($offeringId);

        return ResponseHelper::generateResponse($offeringDays);
    }

    public function update(UpdateOfferingDayRequest $request, int $id)
    {
        $validated = $request->validated();

        $this->offeringDayService->updateOfferingDay($id, $validated);

        return ResponseHelper::generateResponse(null, Response::HTTP_NO_CONTENT);
    }

    public function delete(int $id)
    {
        $this->offeringDayService->deleteOfferingDay($id);

        return ResponseHelper::generateResponse(null, Response::HTTP_NO_CONTENT);
    }
}
