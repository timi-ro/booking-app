<?php

namespace App\Media\Exceptions;

use App\Shared\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DuplicateMediaException extends Exception
{
    public function __construct(protected $message = 'Media of this type already exists for this entity.', protected int $httpStatusCode = Response::HTTP_CONFLICT)
    {
        Log::channel('sentry')->error($this->message);
        parent::__construct($message);
    }

    public function render()
    {
        return ResponseHelper::generateException($this, $this->httpStatusCode);
    }
}
