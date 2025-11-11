<?php

namespace App\Exceptions\User;

use App\Helpers\ResponseHelper;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class InvalidCredentialsException extends Exception
{
    public function __construct(protected $message = "Invalid Credentials", protected int $httpStatusCode = Response::HTTP_UNPROCESSABLE_ENTITY)
    {
        parent::__construct($message);
    }

    public function render()
    {
        return ResponseHelper::generateResponse(['message' => $this->message], $this->httpStatusCode);
    }
}
