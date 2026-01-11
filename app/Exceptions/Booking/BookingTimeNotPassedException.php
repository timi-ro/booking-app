<?php

namespace App\Exceptions\Booking;

use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BookingTimeNotPassedException extends Exception
{
    public function __construct(protected $message = 'Cannot mark as no-show - booking time has not passed yet', protected int $httpStatusCode = Response::HTTP_BAD_REQUEST)
    {
        Log::channel('sentry')->error($this->message);
        parent::__construct($message);
    }

    public function render()
    {
        return ResponseHelper::generateException($this, $this->httpStatusCode);
    }
}
