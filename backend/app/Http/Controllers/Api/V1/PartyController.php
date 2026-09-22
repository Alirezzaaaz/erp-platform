<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Party\StorePartyRequest;
use App\Http\Requests\Party\UpdatePartyRequest;
use App\Http\Resources\PartyResource;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId();

        $query = Party::query()->where('tenant_id', $tenantId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            if ($type === 'customer') {
                $query->whereIn('type', ['customer', 'both']);
            } elseif ($type === 'supplier') {
                $query->whereIn('type', ['supplier', 'both']);
            }
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        if (in_array($sortBy, ['id', 'name', 'code', 'balance', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $parties = $query->paginate($perPage);

        return $this->collectionResponse(
            PartyResource::collection($parties),
            'لیست اشخاص با موفقیت دریافت شد.'
        );
    }

    public function store(StorePartyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $this->getTenantId();
        $data['is_active'] = $data['is_active'] ?? true;

        $party = Party::create($data);

        return $this->successResponse(
            new PartyResource($party),
            'شخص با موفقیت ثبت شد.',
            201
        );
    }

    public function show(Party $party): JsonResponse
    {
        $this->authorizeParty($party);
        return $this->successResponse(
            new PartyResource($party),
            'جزئیات شخص دریافت شد.'
        );
    }

    public function update(UpdatePartyRequest $request, Party $party): JsonResponse
    {
        $this->authorizeParty($party);
        $party->update($request->validated());

        return $this->successResponse(
            new PartyResource($party->fresh()),
            'شخص با موفقیت ویرایش شد.'
        );
    }

    public function destroy(Party $party): JsonResponse
    {
        $this->authorizeParty($party);

        if ($party->invoices()->exists()) {
            return $this->errorResponse(
                'این شخص دارای فاکتور است و قابل حذف نیست. می‌توانید آن را غیرفعال کنید.',
                422
            );
        }

        $party->delete();

        return $this->successResponse(null, 'شخص با موفقیت حذف شد.');
    }

    protected function authorizeParty(Party $party): void
    {
        if ($party->tenant_id !== $this->getTenantId()) {
            abort(404, 'شخص یافت نشد.');
        }
    }
}
