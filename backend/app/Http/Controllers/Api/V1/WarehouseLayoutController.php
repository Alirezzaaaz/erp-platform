<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\WarehouseMap\StoreLayoutRequest;
use App\Http\Requests\WarehouseMap\UpdateLayoutRequest;
use App\Http\Resources\WarehouseLayoutResource;
use App\Models\Warehouse;
use App\Models\WarehouseLayout;
use App\Services\WarehouseLayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WarehouseLayoutController extends BaseApiController
{
    public function __construct(protected WarehouseLayoutService $layoutService) {}

    /**
     * لیست نقشه‌ها
     */
    public function index(Request $request): JsonResponse
    {
        $query = WarehouseLayout::query()
            ->where('tenant_id', $this->getTenantId())
            ->with(['warehouse:id,name,code', 'creator:id,name']);

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $query->orderBy('warehouse_id')->orderBy('version', 'desc');

        $perPage = min((int) $request->input('per_page', 20), 100);
        $layouts = $query->paginate($perPage);

        return $this->collectionResponse(
            WarehouseLayoutResource::collection($layouts),
            'لیست نقشه‌ها دریافت شد.'
        );
    }

    /**
     * ایجاد نقشه جدید
     */
    public function store(StoreLayoutRequest $request): JsonResponse
    {
        $layout = $this->layoutService->createLayout(
            tenantId: $this->getTenantId(),
            warehouseId: (int) $request->warehouse_id,
            data: $request->validated(),
            userId: $this->getUserId()
        );

        $layout->load(['warehouse', 'creator']);

        return $this->successResponse(
            new WarehouseLayoutResource($layout),
            'نقشه جدید ایجاد شد.',
            201
        );
    }

    /**
     * نمایش نقشه کامل با موقعیت‌ها
     */
    public function show(WarehouseLayout $layout): JsonResponse
    {
        $this->authorizeLayout($layout);
        $layout->load(['warehouse', 'locations', 'creator', 'publisher']);

        return $this->successResponse(
            new WarehouseLayoutResource($layout),
            'نقشه دریافت شد.'
        );
    }

    /**
     * به‌روزرسانی متادیتا
     */
    public function update(UpdateLayoutRequest $request, WarehouseLayout $layout): JsonResponse
    {
        $this->authorizeLayout($layout);

        try {
            $layout = $this->layoutService->updateLayout($layout, $request->validated());

            return $this->successResponse(
                new WarehouseLayoutResource($layout->load('warehouse')),
                'نقشه به‌روزرسانی شد.'
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * حذف نقشه (فقط draft)
     */
    public function destroy(WarehouseLayout $layout): JsonResponse
    {
        $this->authorizeLayout($layout);

        if ($layout->status === 'published') {
            return $this->errorResponse('نقشه منتشر شده قابل حذف نیست. ابتدا آن را آرشیو کنید.', 422);
        }

        $layout->locations()->delete();
        $layout->delete();

        return $this->successResponse(null, 'نقشه حذف شد.');
    }

    /**
     * انتشار نقشه
     */
    public function publish(WarehouseLayout $layout): JsonResponse
    {
        $this->authorizeLayout($layout);

        try {
            $layout = $this->layoutService->publishLayout($layout, $this->getUserId());

            return $this->successResponse(
                new WarehouseLayoutResource($layout->load(['warehouse', 'locations'])),
                'نقشه با موفقیت منتشر شد.'
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * کلون کردن نقشه (برای ویرایش نسخه جدید)
     */
    public function clone(WarehouseLayout $layout): JsonResponse
    {
        $this->authorizeLayout($layout);

        $newLayout = $this->layoutService->cloneLayout($layout, $this->getUserId());

        return $this->successResponse(
            new WarehouseLayoutResource($newLayout->load(['warehouse', 'locations'])),
            'نسخه جدید برای ویرایش ساخته شد.',
            201
        );
    }

    /**
     * دریافت نقشه منتشرشده انبار
     */
    public function published(Warehouse $warehouse): JsonResponse
    {
        if ($warehouse->tenant_id !== $this->getTenantId()) {
            abort(404);
        }

        $layout = $warehouse->publishedLayout();

        if (!$layout) {
            return $this->errorResponse('نقشه منتشرشده‌ای برای این انبار وجود ندارد.', 404);
        }

        $layout->load(['locations', 'warehouse']);

        return $this->successResponse(
            new WarehouseLayoutResource($layout),
            'نقشه منتشرشده دریافت شد.'
        );
    }

    /**
     * آمار نقشه
     */
    public function stats(WarehouseLayout $layout): JsonResponse
    {
        $this->authorizeLayout($layout);
        $layout->load('locations');

        return $this->successResponse(
            $this->layoutService->getLayoutStats($layout),
            'آمار نقشه دریافت شد.'
        );
    }

    protected function authorizeLayout(WarehouseLayout $layout): void
    {
        if ($layout->tenant_id !== $this->getTenantId()) {
            abort(404, 'نقشه یافت نشد.');
        }
    }
}
