<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class InvoiceController extends BaseApiController
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()
            ->where('tenant_id', $this->getTenantId())
            ->with(['party:id,code,name,company_name', 'warehouse:id,name', 'creator:id,name']);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($partyId = $request->input('party_id')) {
            $query->where('party_id', $partyId);
        }

        if ($from = $request->input('from_date')) {
            $query->where('issue_date', '>=', $from);
        }

        if ($to = $request->input('to_date')) {
            $query->where('issue_date', '<=', $to);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('party', function ($p) use ($search) {
                      $p->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $query->orderBy('issue_date', 'desc')->orderBy('id', 'desc');

        $perPage = min((int) $request->input('per_page', 30), 100);
        $invoices = $query->paginate($perPage);

        return $this->collectionResponse(
            InvoiceResource::collection($invoices),
            'لیست فاکتورها دریافت شد.'
        );
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->createInvoice(
                tenantId: $this->getTenantId(),
                type: $request->type,
                partyId: (int) $request->party_id,
                items: $request->items,
                warehouseId: (int) $request->warehouse_id,
                issueDate: $request->issue_date,
                dueDate: $request->due_date,
                notes: $request->notes,
                globalDiscount: (float) ($request->global_discount ?? 0),
                userId: $this->getUserId()
            );

            return $this->successResponse(
                new InvoiceResource($invoice->load(['party', 'warehouse', 'items.product', 'creator'])),
                'فاکتور با موفقیت ثبت شد.',
                201
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($invoice);
        $invoice->load(['party', 'warehouse', 'items.product', 'creator', 'approver']);

        return $this->successResponse(
            new InvoiceResource($invoice),
            'جزئیات فاکتور دریافت شد.'
        );
    }

    /**
     * تایید فاکتور (نهایی‌سازی)
     */
    public function confirm(Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($invoice);

        try {
            $invoice = $this->invoiceService->confirmInvoice($invoice, $this->getUserId());

            return $this->successResponse(
                new InvoiceResource($invoice->load(['party', 'warehouse', 'items.product'])),
                'فاکتور با موفقیت تایید شد و اثرات آن اعمال گردید.'
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * لغو فاکتور
     */
    public function cancel(Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($invoice);

        try {
            $invoice = $this->invoiceService->cancelInvoice($invoice, $this->getUserId());

            return $this->successResponse(
                new InvoiceResource($invoice->load(['party', 'warehouse', 'items.product'])),
                'فاکتور با موفقیت لغو شد.'
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($invoice);

        if ($invoice->status === 'confirmed') {
            return $this->errorResponse(
                'فاکتور تایید شده قابل حذف نیست. ابتدا آن را لغو کنید.',
                422
            );
        }

        $invoice->items()->delete();
        $invoice->delete();

        return $this->successResponse(null, 'فاکتور با موفقیت حذف شد.');
    }

    protected function authorizeInvoice(Invoice $invoice): void
    {
        if ($invoice->tenant_id !== $this->getTenantId()) {
            abort(404, 'فاکتور یافت نشد.');
        }
    }
}
