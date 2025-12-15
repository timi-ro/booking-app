<?php

namespace App\Exceptions\Booking;

use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BookingAlreadyCancelledException extends Exception
{
    public function __construct(protected $message = "This booking has already been cancelled", protected int $httpStatusCode = Response::HTTP_BAD_REQUEST)
    {
        Log::channel('sentry')->error($this->message);
        parent::__construct($message);
    }

    public function render()
    {
        return ResponseHelper::generateException($this, $this->httpStatusCode);
    }
}
