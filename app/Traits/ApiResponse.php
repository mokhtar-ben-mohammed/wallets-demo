<?php

namespace App\Traits;

trait ApiResponse
{
    protected function success(
        string $message = 'Success',
        $data = null,
        int $statusCode = 200
    ) {
        return response()->json([
            'isSuccess' => true,
            'message'   => $message,
            'data'      => $data,
        ], $statusCode);
    }

    protected function error(
        string $message = 'Error',
        int $statusCode = 400
    ) {
        return response()->json([
            'isSuccess' => false,
            'message'   => $message,
            'data'      => null,
        ], $statusCode);
    }
}