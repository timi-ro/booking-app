<?php

namespace App\Exceptions;

use App\Exceptions\Offering\OfferingNotFoundException;
use App\Exceptions\OfferingDay\OfferingDayNotFoundException;
use App\Exceptions\OfferingTimeSlot\OfferingTimeSlotNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (OfferingNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'code' => 404,
                    'message' => $e->getMessage(),
                    'data' => []
                ], 404);
            }
        });

        $this->renderable(function (OfferingDayNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'code' => 404,
                    'message' => $e->getMessage(),
                    'data' => []
                ], 404);
            }
        });

        $this->renderable(function (OfferingTimeSlotNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'code' => 404,
                    'message' => $e->getMessage(),
                    'data' => []
                ], 404);
            }
        });
    }

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
