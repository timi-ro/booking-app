<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{

    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'code' => $exception->status,
            'message' => collect($exception->errors())->first(),
            'data' => [
                'errors' => $exception->errors(),
                'exception' => in_array(config('app.env'), ['local', 'dev']) ? $this->convertExceptionToArray($exception) : [],
                'request' => $request->all()
            ]
        ], $exception->status);
    }

}
