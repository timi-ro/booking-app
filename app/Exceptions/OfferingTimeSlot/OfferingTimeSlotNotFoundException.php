<?php

namespace App\Exceptions\OfferingTimeSlot;

use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OfferingTimeSlotNotFoundException extends Exception
{
    public function __construct(protected $message = 'Time slot not found', protected int $httpStatusCode = Response::HTTP_NOT_FOUND)
    {
        Log::channel('sentry')->error($this->message);
        parent::__construct($message);
    }

    public function render()
    {
        return ResponseHelper::generateException($this, $this->httpStatusCode);
    }
}
