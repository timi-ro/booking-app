<?php

namespace App\Media\Exceptions;

use App\Shared\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InvalidMediaEntityException extends Exception
{
    public function __construct(protected $message = 'invalid type of entity for media.', protected int $httpStatusCode = Response::HTTP_UNPROCESSABLE_ENTITY)
    {
        Log::channel('sentry')->error($this->message);
        parent::__construct($message);
    }

    public function render()
    {
        return ResponseHelper::generateException($this, $this->httpStatusCode);
    }
}
