<?php

namespace App;

use Illuminate\Http\JsonResponse;

class Helper
{
    /**
     * @param $code
     * @param null $data
     * @param $message
     * @return JsonResponse
     */
    public static function successResponse($code, $message, $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'result' => $data
        ], $code);
    }

    /**
     * @param $code
     * @param null $errors
     * @param $message
     * @return JsonResponse
     */
    public static function errorResponse($code, $message, $errors = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
}
