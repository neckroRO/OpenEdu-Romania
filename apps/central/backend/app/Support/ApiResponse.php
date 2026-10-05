<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data,
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        return self::json([
            'data' => $data,
            'meta' => array_merge(
                self::baseMeta(),
                $meta
            ),
        ], $status);
    }

    public static function created(
        mixed $data,
        array $meta = []
    ): JsonResponse {
        return self::success($data, 201, $meta);
    }

    public static function error(
        string $code,
        string $message,
        int $status,
        array $details = [],
        array $headers = []
    ): JsonResponse {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== []) {
            $error['details'] = $details;
        }

        return self::json([
            'error' => $error,
            'meta' => self::baseMeta(),
        ], $status, $headers);
    }

    private static function baseMeta(): array
    {
        return [
            'request_id' => self::requestId(),
        ];
    }

    private static function requestId(): ?string
    {
        return request()->attributes->get('request_id');
    }

    private static function json(
        array $payload,
        int $status,
        array $headers = []
    ): JsonResponse {
        $response = response()->json(
            $payload,
            $status,
            $headers
        );

        $requestId = self::requestId();

        if ($requestId !== null) {
            $response->headers->set('X-Request-ID', $requestId);
        }

        return $response;
    }
}
