<?php

namespace App\Shared\Helpers;

use Symfony\Component\HttpFoundation\Response;

class ResponseHelper
{
    public static function generateResponse($data, int $status = Response::HTTP_OK, string $errorMessage = '')
    {
        return response()->json([
            'errorMessage' => $errorMessage,
            'data' => $data,
        ], $status);
    }

    public static function generateException(\Exception $exception, int $status, $data = [])
    {
        return response()->json([
            'errorMessage' => $exception->getMessage(),
            'data' => $data,
        ], $status);
    }
}
