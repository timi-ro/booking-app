<?php

namespace App\Http\Controllers\Agency;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\CreateAvailabilityRequest;
use App\Services\AvailabilityService;
use Symfony\Component\HttpFoundation\Response;

class AvailabilityController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService,
    )
    {
    }

    public function create(CreateAvailabilityRequest $request)
    {
        $validated = $request->validated();

        $availability = $this->availabilityService->createAvailability($validated);

        return ResponseHelper::generateResponse($availability, Response::HTTP_CREATED);
    }
}
