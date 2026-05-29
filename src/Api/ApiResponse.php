<?php

namespace App\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Standardised JSON envelope, mirroring the legacy backend/utils/Response.php
 * so the existing frontend ({success, data, message} / {success, error, code})
 * keeps working unchanged.
 */
final class ApiResponse
{
    public static function success(mixed $data, int $status = 200, ?string $message = null): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];
        if ($message !== null) {
            $payload['message'] = $message;
        }
        return new JsonResponse($payload, $status);
    }

    public static function error(string $message, string $code = 'BAD_REQUEST', int $status = 400): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error'   => $message,
            'code'    => $code,
        ], $status);
    }
}
