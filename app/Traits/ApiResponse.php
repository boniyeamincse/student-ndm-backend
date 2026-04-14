<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a standardised success JSON response.
     */
    protected function success(
        mixed  $data    = null,
        string $message = 'Request successful.',
        int    $status  = 200,
        array  $meta    = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return a standardised error JSON response.
     */
    protected function error(
        string $message = 'Something went wrong.',
        int    $status  = 400,
        array  $errors  = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
