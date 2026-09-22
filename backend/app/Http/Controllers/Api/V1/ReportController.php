<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseApiController
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function trialBalance(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        $data = $this->accountingService->getTrialBalance(
            tenantId: $this->getTenantId(),
            fromDate: $request->input('from_date'),
            toDate: $request->input('to_date')
        );

        return $this->successResponse($data, 'تراز آزمایشی دریافت شد.');
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $request->validate(['to_date' => ['nullable', 'date']]);

        $data = $this->accountingService->getBalanceSheet(
            tenantId: $this->getTenantId(),
            toDate: $request->input('to_date')
        );

        return $this->successResponse($data, 'ترازنامه دریافت شد.');
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        $data = $this->accountingService->getIncomeStatement(
            tenantId: $this->getTenantId(),
            fromDate: $request->input('from_date'),
            toDate: $request->input('to_date')
        );

        return $this->successResponse($data, 'صورت سود و زیان دریافت شد.');
    }
}
