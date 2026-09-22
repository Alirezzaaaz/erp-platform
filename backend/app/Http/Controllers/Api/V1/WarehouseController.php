<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId();

        $query = Warehouse::query()->where('tenant_id', $tenantId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('manager_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('is_default', 'desc')->orderBy('created_at', 'desc');

        $perPage = min((int) $request->input('per_page', 20), 100);
        $warehouses = $query->paginate($perPage);

        return $this->collectionResponse(
            WarehouseResource::collection($warehouses),
            'لیست انبارها با موفقیت دریافت شد.'
        );
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $tenantId = $this->getTenantId();

        $warehouse = DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();
            $data['tenant_id'] = $tenantId;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_default'] = $data['is_default'] ?? false;

            // اگر این انبار پیش‌فرض است، بقیه را غیرپیش‌فرض کن
            if ($data['is_default']) {
                Warehouse::where('tenant_id', $tenantId)->update(['is_default' => false]);
            }

            return Warehouse::create($data);
        });

        return $this->successResponse(
            new WarehouseResource($warehouse),
            'انبار با موفقیت ثبت شد.',
            201
        );
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        $this->authorizeWarehouse($warehouse);
        return $this->successResponse(
            new WarehouseResource($warehouse),
            'جزئیات انبار دریافت شد.'
        );
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorizeWarehouse($warehouse);
        $tenantId = $this->getTenantId();

        DB::transaction(function () use ($request, $warehouse, $tenantId) {
            $data = $request->validated();

            if (!empty($data['is_default']) && $data['is_default']) {
                Warehouse::where('tenant_id', $tenantId)
                    ->where('id', '!=', $warehouse->id)
                    ->update(['is_default' => false]);
            }

            $warehouse->update($data);
        });

        return $this->successResponse(
            new WarehouseResource($warehouse->fresh()),
            'انبار با موفقیت ویرایش شد.'
        );
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorizeWarehouse($warehouse);

        // بررسی وجود موجودی
        if ($warehouse->stocks()->where('quantity', '>', 0)->exists()) {
            return $this->errorResponse(
                'این انبار دارای موجودی است و قابل حذف نیست. ابتدا موجودی را منتقل کنید.',
                422
            );
        }

        if ($warehouse->is_default) {
            return $this->errorResponse(
                'انبار پیش‌فرض قابل حذف نیست. ابتدا انبار پیش‌فرض دیگری تعیین کنید.',
                422
            );
        }

        $warehouse->delete();

        return $this->successResponse(null, 'انبار با موفقیت حذف شد.');
    }

    protected function authorizeWarehouse(Warehouse $warehouse): void
    {
        if ($warehouse->tenant_id !== $this->getTenantId()) {
            abort(404, 'انبار یافت نشد.');
        }
    }
}
