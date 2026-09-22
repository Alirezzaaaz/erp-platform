<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId();

        $query = Account::query()->where('tenant_id', $tenantId);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // حالت درختی (فقط ریشه‌ها با فرزندان)
        if ($request->boolean('tree')) {
            $accounts = Account::where('tenant_id', $tenantId)
                ->whereNull('parent_id')
                ->with('children.children.children')
                ->orderBy('code')
                ->get();

            return $this->successResponse(
                AccountResource::collection($accounts),
                'درخت حساب‌ها دریافت شد.'
            );
        }

        // حالت فلت
        $accounts = $query->orderBy('code')->get();

        return $this->successResponse(
            AccountResource::collection($accounts),
            'لیست حساب‌ها دریافت شد.'
        );
    }

    public function show(Account $account): JsonResponse
    {
        $this->authorizeAccount($account);
        $account->load(['parent', 'children']);

        return $this->successResponse(
            new AccountResource($account),
            'جزئیات حساب دریافت شد.'
        );
    }

    /**
     * گزارش دفتر کل یک حساب
     */
    public function ledger(Request $request, int $accountId): JsonResponse
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        $service = app(\App\Services\AccountingService::class);

        $data = $service->getAccountLedger(
            tenantId: $this->getTenantId(),
            accountId: $accountId,
            fromDate: $request->input('from_date'),
            toDate: $request->input('to_date')
        );

        return $this->successResponse($data, 'دفتر کل دریافت شد.');
    }

    protected function authorizeAccount(Account $account): void
    {
        if ($account->tenant_id !== $this->getTenantId()) {
            abort(404, 'حساب یافت نشد.');
        }
    }
}
