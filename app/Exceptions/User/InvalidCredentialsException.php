<?php

namespace App\Exceptions\User;

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
        return response()->json([
            'message' => $this->message,
        ], $this->httpStatusCode);
    }
}
