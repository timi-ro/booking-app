<?php

namespace App\Exceptions\Booking;

use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ReservationAlreadyPaidException extends Exception
{
    public function __construct(protected $message = 'This reservation has already been paid and processed', protected int $httpStatusCode = Response::HTTP_CONFLICT)
    {
        Log::channel('sentry')->error($this->message);
        parent::__construct($message);
    }

    public function render()
    {
        return ResponseHelper::generateException($this, $this->httpStatusCode);
    }
}
