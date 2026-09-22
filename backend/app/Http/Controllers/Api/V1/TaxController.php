<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invoice;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class TaxController extends BaseApiController
{
    public function __construct(protected TaxService $taxService) {}

    public function sendInvoice(Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($invoice);

        try {
            $result = $this->taxService->sendAndRecord($invoice);
            $invoice->refresh();

            if ($result['success']) {
                return $this->successResponse([
                    'tax_uid' => $invoice->tax_uid,
                    'tax_reference_id' => $invoice->tax_reference_id,
                    'tax_status' => $invoice->tax_status,
                    'sent_at' => $invoice->tax_sent_at?->toIso8601String(),
                    'mock' => $result['mock'] ?? false,
                ], ($result['mock'] ?? false)
                    ? 'فاکتور در حالت تست با موفقیت ارسال شد.'
                    : 'فاکتور با موفقیت به سامانه مودیان ارسال شد.'
                );
            }

            return $this->errorResponse($result['error'] ?? 'خطا در ارسال به سامانه مودیان', 422);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function queryStatus(Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($invoice);
        $result = $this->taxService->queryStatus($invoice);
        return $this->successResponse($result, 'وضعیت فاکتور دریافت شد.');
    }

    public function previewPayload(Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($invoice);
        $payload = $this->taxService->buildInvoicePayload($invoice);
        return $this->successResponse($payload, 'پیش‌نمایش payload مودیان.');
    }

    protected function authorizeInvoice(Invoice $invoice): void
    {
        if ($invoice->tenant_id !== $this->getTenantId()) {
            abort(404, 'فاکتور یافت نشد.');
        }
    }
}
