<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Unit::query()->where('tenant_id', $this->getTenantId());

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('name');

        $perPage = min((int) $request->input('per_page', 50), 200);
        $units = $query->paginate($perPage);

        return $this->collectionResponse(
            UnitResource::collection($units),
            'لیست واحدها دریافت شد.'
        );
    }

    public function store(StoreUnitRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $this->getTenantId();
        $data['is_active'] = $data['is_active'] ?? true;
        $data['conversion_factor'] = $data['conversion_factor'] ?? 1;

        $unit = Unit::create($data);

        return $this->successResponse(
            new UnitResource($unit),
            'واحد با موفقیت ثبت شد.',
            201
        );
    }

    public function show(Unit $unit): JsonResponse
    {
        $this->authorizeUnit($unit);
        return $this->successResponse(new UnitResource($unit), 'جزئیات واحد دریافت شد.');
    }

    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $this->authorizeUnit($unit);
        $unit->update($request->validated());

        return $this->successResponse(
            new UnitResource($unit->fresh()),
            'واحد با موفقیت ویرایش شد.'
        );
    }

    public function destroy(Unit $unit): JsonResponse
    {
        $this->authorizeUnit($unit);

        if ($unit->products()->exists()) {
            return $this->errorResponse(
                'این واحد در کالاها استفاده شده و قابل حذف نیست.',
                422
            );
        }

        $unit->delete();

        return $this->successResponse(null, 'واحد با موفقیت حذف شد.');
    }

    protected function authorizeUnit(Unit $unit): void
    {
        if ($unit->tenant_id !== $this->getTenantId()) {
            abort(404, 'واحد یافت نشد.');
        }
    }
}
