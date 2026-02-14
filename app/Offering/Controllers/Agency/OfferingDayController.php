<?php

namespace App\Offering\Controllers\Agency;

use App\Shared\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\OfferingDay\CreateOfferingDayRequest;
use App\Http\Requests\OfferingDay\ListOfferingDaysRequest;
use App\Http\Requests\OfferingDay\UpdateOfferingDayRequest;
use App\Offering\Services\OfferingDayService;
use Symfony\Component\HttpFoundation\Response;

class OfferingDayController extends Controller
{
    public function __construct(
        protected OfferingDayService $offeringDayService,
    ) {}

    public function create(CreateOfferingDayRequest $request)
    {
        $validated = $request->validated();

        $offeringDay = $this->offeringDayService->createOfferingDay($validated);

        return ResponseHelper::generateResponse($offeringDay, Response::HTTP_CREATED);
    }

    public function index(ListOfferingDaysRequest $request)
    {
        $validated = $request->validated();

        $offeringDays = $this->offeringDayService->getOfferingDays($validated['offering_id']);

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
