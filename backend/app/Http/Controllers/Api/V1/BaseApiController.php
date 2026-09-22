<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class BaseApiController extends Controller
{
    /**
     * پاسخ موفق
     */
    protected function successResponse(mixed $data = null, string $message = 'عملیات با موفقیت انجام شد.', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * پاسخ خطا
     */
    protected function errorResponse(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * پاسخ Collection با متادیتای Pagination
     */
    protected function collectionResponse(ResourceCollection $collection, string $message = 'لیست با موفقیت دریافت شد.'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $collection->collection,
            'meta' => [
                'current_page' => $collection->currentPage(),
                'from' => $collection->firstItem(),
                'last_page' => $collection->lastPage(),
                'per_page' => $collection->perPage(),
                'to' => $collection->lastItem(),
                'total' => $collection->total(),
            ],
            'links' => [
                'first' => $collection->url(1),
                'last' => $collection->url($collection->lastPage()),
                'prev' => $collection->previousPageUrl(),
                'next' => $collection->nextPageUrl(),
            ],
        ]);
    }

    /**
     * دریافت tenant_id از کاربر جاری
     */
    protected function getTenantId(): int
    {
        return (int) auth('api')->payload()->get('tenant_id');
    }

    /**
     * دریافت user_id از کاربر جاری
     */
    protected function getUserId(): int
    {
        return (int) auth('api')->id();
    }
}
