<?php

namespace App\Helpers;

class ApiResponse
{
    public static function sendResponse($status = 'success', $message = 'Success', $statusCode = 404, $data = null,)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'error' => $data,
            'statusCode' => $statusCode
        ], 200);
    }
}
