<?php

namespace App\Http\Controllers\Agency;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\OfferingTimeSlot\BulkCreateTimeSlotsRequest;
use App\Http\Requests\OfferingTimeSlot\CreateTimeSlotRequest;
use App\Http\Requests\OfferingTimeSlot\UpdateTimeSlotRequest;
use App\Services\OfferingTimeSlotService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OfferingTimeSlotController extends Controller
{
    public function __construct(
        protected OfferingTimeSlotService $offeringTimeSlotService,
    ) {
    }

    public function create(CreateTimeSlotRequest $request)
    {
        $validated = $request->validated();

        $timeSlot = $this->offeringTimeSlotService->createTimeSlot($validated);

        return ResponseHelper::generateResponse($timeSlot, Response::HTTP_CREATED);
    }

    public function bulkCreate(BulkCreateTimeSlotsRequest $request)
    {
        $validated = $request->validated();

        $timeSlots = $this->offeringTimeSlotService->bulkCreateTimeSlots(
            $validated['offering_day_id'],
            $validated['time_slots']
        );

        return ResponseHelper::generateResponse($timeSlots, Response::HTTP_CREATED);
    }

    public function index(Request $request)
    {
        $offeringDayId = $request->query('offering_day_id');

        if (!$offeringDayId) {
            return ResponseHelper::generateResponse(
                ['error' => 'offering_day_id query parameter is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $timeSlots = $this->offeringTimeSlotService->getTimeSlots($offeringDayId);

        return ResponseHelper::generateResponse($timeSlots);
    }

    public function update(UpdateTimeSlotRequest $request, int $id)
    {
        $validated = $request->validated();

        $this->offeringTimeSlotService->updateTimeSlot($id, $validated);

        return ResponseHelper::generateResponse(null, Response::HTTP_NO_CONTENT);
    }

    public function delete(int $id)
    {
        $this->offeringTimeSlotService->deleteTimeSlot($id);

        return ResponseHelper::generateResponse(null, Response::HTTP_NO_CONTENT);
    }
}
