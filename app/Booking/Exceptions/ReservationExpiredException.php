<?php

namespace App\Booking\Exceptions;

use App\Shared\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ReservationExpiredException extends Exception
{
    public function __construct(protected $message = 'Your reservation has expired. Please try booking again', protected int $httpStatusCode = Response::HTTP_GONE)
    {
        Log::channel('sentry')->error($this->message);
        parent::__construct($message);
    }

    public function render()
    {
        return ResponseHelper::generateException($this, $this->httpStatusCode);
    }
}
