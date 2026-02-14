<?php

namespace App\Media\Exceptions;

use App\Shared\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MediaUploadFailedException extends Exception
{
    public function __construct(protected $message = 'Upload Failed.', protected int $httpStatusCode = Response::HTTP_FAILED_DEPENDENCY)
    {
        Log::channel('sentry')->error($this->message);
        parent::__construct($message);
    }

    public function render()
    {
        return ResponseHelper::generateException($this, $this->httpStatusCode);
    }
}
