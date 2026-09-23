<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\WarehouseMap\BulkLocationRequest;
use App\Http\Requests\WarehouseMap\StoreLocationRequest;
use App\Http\Resources\WarehouseLocationResource;
use App\Models\WarehouseLayout;
use App\Models\WarehouseLocation;
use App\Services\WarehouseLayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WarehouseLocationController extends BaseApiController
{
    public function __construct(protected WarehouseLayoutService $layoutService) {}

    /**
     * لیست موقعیت‌های یک نقشه
     */
    public function index(Request $request, WarehouseLayout $layout): JsonResponse
    {
        $this->authorizeLayout($layout);

        $query = WarehouseLocation::where('warehouse_layout_id', $layout->id);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $locations = $query->orderBy('type')->orderBy('code')->get();

        return $this->successResponse(
            WarehouseLocationResource::collection($locations),
            'لیست موقعیت‌ها دریافت شد.'
        );
    }

    /**
     * افزودن موقعیت جدید
     */
    public function store(StoreLocationRequest $request, WarehouseLayout $layout): JsonResponse
    {
        $this->authorizeLayout($layout);

        try {
            $location = $this->layoutService->addLocation($layout, $request->validated());

            return $this->successResponse(
                new WarehouseLocationResource($location),
                'موقعیت با موفقیت اضافه شد.',
                201
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * افزودن انبوه (Import)
     */
    public function bulkStore(BulkLocationRequest $request, WarehouseLayout $layout): JsonResponse
    {
        $this->authorizeLayout($layout);

        try {
            $result = $this->layoutService->bulkAddLocations($layout, $request->locations);

            return $this->successResponse([
                'created' => WarehouseLocationResource::collection(collect($result['created'])),
                'errors' => $result['errors'],
                'created_count' => $result['created_count'],
                'error_count' => $result['error_count'],
            ], "{$result['created_count']} موقعیت اضافه شد. {$result['error_count']} خطا رخ داد.");
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * نمایش موقعیت
     */
    public function show(WarehouseLocation $location): JsonResponse
    {
        $this->authorizeLocation($location);

        return $this->successResponse(
            new WarehouseLocationResource($location->load('layout')),
            'موقعیت دریافت شد.'
        );
    }

    /**
     * به‌روزرسانی موقعیت
     */
    public function update(Request $request, WarehouseLocation $location): JsonResponse
    {
        $this->authorizeLocation($location);

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50'],
            'type' => ['sometimes', 'in:zone,aisle,rack,shelf,bin'],
            'name' => ['sometimes', 'string', 'max:255'],
            'pos_x' => ['sometimes', 'numeric', 'min:0'],
            'pos_y' => ['sometimes', 'numeric', 'min:0'],
            'pos_z' => ['sometimes', 'numeric', 'min:0'],
            'width' => ['sometimes', 'numeric', 'min:0.1'],
            'depth' => ['sometimes', 'numeric', 'min:0.1'],
            'height' => ['sometimes', 'numeric', 'min:0.1'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        try {
            $location = $this->layoutService->updateLocation($location, $validated);

            return $this->successResponse(
                new WarehouseLocationResource($location),
                'موقعیت به‌روزرسانی شد.'
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * حذف موقعیت
     */
    public function destroy(WarehouseLocation $location): JsonResponse
    {
        $this->authorizeLocation($location);

        try {
            $this->layoutService->deleteLocation($location);

            return $this->successResponse(null, 'موقعیت حذف شد.');
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    protected function authorizeLayout(WarehouseLayout $layout): void
    {
        if ($layout->tenant_id !== $this->getTenantId()) {
            abort(404, 'نقشه یافت نشد.');
        }
    }

    protected function authorizeLocation(WarehouseLocation $location): void
    {
        if ($location->tenant_id !== $this->getTenantId()) {
            abort(404, 'موقعیت یافت نشد.');
        }
    }
}
