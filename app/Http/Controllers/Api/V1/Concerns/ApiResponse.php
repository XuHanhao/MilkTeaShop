<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'OK', int $status = Response::HTTP_OK): JsonResponse
    {
        // If it's a paginated object, simplify the return format
        if ($data instanceof LengthAwarePaginator) {
            $items = $data->items();
            // Ensure items is in array format
            if (is_array($items)) {
                $items = array_values($items);
            } else {
                $items = $items->toArray();
            }
            
            $data = [
                'current_page' => $data->currentPage(),
                'data' => $items,
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ];
        }

        return response()->json([
            'code' => 0,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error(string $message, int $status = Response::HTTP_BAD_REQUEST, int $code = 1, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}

