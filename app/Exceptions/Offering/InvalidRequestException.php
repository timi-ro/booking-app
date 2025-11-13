<?php

namespace App\Exceptions\Offering;

use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InvalidRequestException extends Exception
{
    public function __construct(protected $message = "At least one field must be provided for update.", protected int $httpStatusCode = Response::HTTP_BAD_REQUEST)
    {
        Log::channel('sentry')->error($this->message);
        parent::__construct($message);
    }

    public function render()
    {
        return ResponseHelper::generateException($this, $this->httpStatusCode);
    }
}
