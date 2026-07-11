<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Http\JsonResponse;

abstract class FarmerApiController extends Controller
{
    protected function success(mixed $data, array $meta = [], string $message = 'OK', int $status = 200): JsonResponse
    {
        if ($data instanceof JsonResource) {
            $data = $data->resolve();
        }

        return response()->json([
            'message' => $message,
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }

    protected function paginated(AbstractPaginator $paginator, mixed $items, array $meta = [], string $message = 'OK'): JsonResponse
    {
        if ($items instanceof JsonResource) {
            $items = $items->resolve();
        }

        return response()->json([
            'message' => $message,
            'data' => $items,
            'meta' => array_merge($meta, [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]),
        ]);
    }

    protected function notImplemented(string $feature): JsonResponse
    {
        return response()->json([
            'message' => sprintf('%s is prepared for the new Farmer PWA API but has not been implemented yet.', $feature),
            'data' => null,
            'meta' => (object) [],
        ], 501);
    }
}
