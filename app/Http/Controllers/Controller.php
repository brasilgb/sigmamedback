<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function successResponse(mixed $data = null, string $message = 'OK', array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json($this->buildResponse($data, $message, $meta), $status);
    }

    protected function errorResponse(string $message, int $status = 422, mixed $data = null, array $meta = []): JsonResponse
    {
        return response()->json($this->buildResponse($data, $message, $meta), $status);
    }

    protected function buildResponse(mixed $data, string $message, array $meta): array
    {
        return [
            'data' => $data === null ? new \stdClass : $data,
            'meta' => empty($meta) ? new \stdClass : $meta,
            'message' => $message,
        ];
    }
}
