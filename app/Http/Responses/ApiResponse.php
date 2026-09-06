<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * @param  array<string, mixed>|list<mixed>|\stdClass  $data
     */
    public static function success(string $message, mixed $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => self::normalizeData($data),
            'errors' => null,
        ], $status);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function error(string $message, ?array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => ($errors === null || $errors === []) ? (object) [] : $errors,
        ], $status);
    }

    protected static function normalizeData(mixed $data): mixed
    {
        if ($data === []) {
            return (object) [];
        }

        return $data;
    }
}
