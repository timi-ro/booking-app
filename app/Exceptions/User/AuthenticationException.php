<?php

namespace App\Exceptions\User;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationException extends Exception
{
    public function __construct(protected $message = "You are not allowed to access this page.", protected int $httpStatusCode = Response::HTTP_UNPROCESSABLE_ENTITY)
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
