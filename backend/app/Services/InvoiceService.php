<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Party;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AccountingService $accountingService
    ) {}

    /**
     * ایجاد فاکتور (فروش یا خرید)
     */
    public function createInvoice(
        int $tenantId,
        string $type,
        int $partyId,
        array $items,
        ?int $warehouseId = null,
        ?string $issueDate = null,
        ?string $dueDate = null,
        ?string $notes = null,
        float $globalDiscount = 0,
        ?int $userId = null
    ): Invoice {
        $validTypes = ['sale', 'purchase', 'sale_return', 'purchase_return'];
        if (!in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException('نوع فاکتور نامعتبر است.');
        }

        if (empty($items)) {
            throw new InvalidArgumentException('فاکتور باید حداقل یک ردیف داشته باشد.');
        }

        // انبار فقط برای فاکتورهای کالایی الزامی است
        if (!$warehouseId) {
            throw new InvalidArgumentException('انبار الزامی است.');
        }

        return DB::transaction(function () use (
            $tenantId, $type, $partyId, $items, $warehouseId,
            $issueDate, $dueDate, $notes, $globalDiscount, $userId
        ) {
            $party = Party::where('tenant_id', $tenantId)->findOrFail($partyId);

            // اعتبارسنجی نوع طرف حساب
            if (in_array($type, ['sale', 'sale_return']) && !$party->isCustomer()) {
                throw new InvalidArgumentException('این شخص مشتری نیست.');
            }
            if (in_array($type, ['purchase', 'purchase_return']) && !$party->isSupplier()) {
                throw new InvalidArgumentException('این شخص تأمین‌کننده نیست.');
            }

            // شماره فاکتور
            $number = $this->generateInvoiceNumber($tenantId, $type);

            // ایجاد فاکتور
            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'type' => $type,
                'number' => $number,
                'party_id' => $partyId,
                'warehouse_id' => $warehouseId,
                'issue_date' => $issueDate ?? now()->toDateString(),
                'due_date' => $dueDate,
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'notes' => $notes,
                'created_by' => $userId ?? auth('api')->id(),
            ]);

            // افزودن ردیف‌ها
            foreach ($items as $index => $item) {
                $this->addItem($invoice, $item, $index);
            }

            // اعمال تخفیف سراسری
            if ($globalDiscount > 0) {
                $invoice->discount_amount = ($invoice->discount_amount ?? 0) + $globalDiscount;
            }

            $invoice->recalculateTotals();

            return $invoice->fresh(['items.product', 'party']);
        });
    }

    /**
     * افزودن ردیف به فاکتور
     */
    protected function addItem(Invoice $invoice, array $item, int $order): InvoiceItem
    {
        $product = Product::where('tenant_id', $invoice->tenant_id)
            ->findOrFail($item['product_id']);

        $qty = (float) ($item['quantity'] ?? 0);
        if ($qty <= 0) {
            throw new InvalidArgumentException('مقدار کالا باید بزرگ‌تر از صفر باشد.');
        }

        // قیمت پیش‌فرض بر اساس نوع فاکتور
        $defaultPrice = in_array($invoice->type, ['sale', 'sale_return'])
            ? (float) $product->sale_price
            : (float) $product->purchase_price;

        $unitPrice = (float) ($item['unit_price'] ?? $defaultPrice);
        $discountPercent = (float) ($item['discount_percent'] ?? 0);
        $taxPercent = (float) ($item['tax_percent'] ?? 9);

        // محاسبات
        $lineTotal = $qty * $unitPrice;
        $discountAmount = $lineTotal * ($discountPercent / 100);
        $afterDiscount = $lineTotal - $discountAmount;
        $taxAmount = $afterDiscount * ($taxPercent / 100);
        $totalWithTax = $afterDiscount + $taxAmount;

        return InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'total_amount' => $afterDiscount,
            'description' => $item['description'] ?? null,
        ]);
    }

    /**
     * تایید فاکتور (نهایی‌سازی):
     *  - کسر/افزودن موجودی انبار
     *  - ثبت سند حسابداری خودکار
     *  - به‌روزرسانی بدهی مشتری/تأمین‌کننده
     */
    public function confirmInvoice(Invoice $invoice, ?int $userId = null): Invoice
    {
        if ($invoice->status === 'confirmed') {
            throw new InvalidArgumentException('فاکتور قبلاً تایید شده است.');
        }

        if ($invoice->status === 'cancelled') {
            throw new InvalidArgumentException('فاکتور لغو شده قابل تایید نیست.');
        }

        return DB::transaction(function () use ($invoice, $userId) {
            $invoice->load('items.product', 'party');

            if ($invoice->items->isEmpty()) {
                throw new InvalidArgumentException('فاکتور ردیفی ندارد.');
            }

            // ۱. به‌روزرسانی موجودی انبار
            $totalCost = $this->updateInventory($invoice);

            // ۲. ثبت سند حسابداری
            $this->createAccountingEntry($invoice, $totalCost);

            // ۳. به‌روزرسانی موجودی حساب طرف
            $this->updatePartyBalance($invoice);

            // ۴. تایید فاکتور
            $invoice->update(['status' => 'confirmed']);

            return $invoice->fresh(['items.product', 'party']);
        });
    }

    /**
     * به‌روزرسانی موجودی انبار بر اساس نوع فاکتور
     */
    protected function updateInventory(Invoice $invoice): float
    {
        $totalCost = 0;
        $userId = $invoice->created_by;

        foreach ($invoice->items as $item) {
            $product = $item->product;

            if (!$product || !$product->track_stock) {
                continue;
            }

            switch ($invoice->type) {
                case 'sale':
                    // خروج از انبار
                    $tx = $this->inventoryService->decreaseStock(
                        tenantId: $invoice->tenant_id,
                        warehouseId: $invoice->warehouse_id,
                        productId: $item->product_id,
                        quantity: (float) $item->quantity,
                        referenceType: 'sale',
                        referenceId: $invoice->id,
                        description: "فاکتور فروش {$invoice->number}",
                        userId: $userId
                    );
                    $totalCost += (float) $tx->total_cost;
                    break;

                case 'sale_return':
                    // ورود به انبار (برگشت از فروش) - با بهای میانگین فعلی
                    $stock = \App\Models\InventoryStock::where('warehouse_id', $invoice->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->first();
                    $unitCost = $stock ? (float) $stock->average_cost : (float) $product->purchase_price;

                    $tx = $this->inventoryService->increaseStock(
                        tenantId: $invoice->tenant_id,
                        warehouseId: $invoice->warehouse_id,
                        productId: $item->product_id,
                        quantity: (float) $item->quantity,
                        unitCost: $unitCost,
                        referenceType: 'sale_return',
                        referenceId: $invoice->id,
                        description: "برگشت از فروش {$invoice->number}",
                        userId: $userId
                    );
                    $totalCost -= (float) $tx->total_cost;
                    break;

                case 'purchase':
                    // ورود به انبار با بهای خرید
                    $tx = $this->inventoryService->increaseStock(
                        tenantId: $invoice->tenant_id,
                        warehouseId: $invoice->warehouse_id,
                        productId: $item->product_id,
                        quantity: (float) $item->quantity,
                        unitCost: (float) $item->unit_price,
                        referenceType: 'purchase',
                        referenceId: $invoice->id,
                        description: "فاکتور خرید {$invoice->number}",
                        userId: $userId
                    );
                    $totalCost += (float) $tx->total_cost;
                    break;

                case 'purchase_return':
                    // خروج از انبار (برگشت به تأمین‌کننده)
                    $tx = $this->inventoryService->decreaseStock(
                        tenantId: $invoice->tenant_id,
                        warehouseId: $invoice->warehouse_id,
                        productId: $item->product_id,
                        quantity: (float) $item->quantity,
                        referenceType: 'purchase_return',
                        referenceId: $invoice->id,
                        description: "برگشت به تأمین‌کننده {$invoice->number}",
                        userId: $userId
                    );
                    $totalCost -= (float) $tx->total_cost;
                    break;
            }
        }

        return abs($totalCost);
    }

    /**
     * ثبت سند حسابداری خودکار
     */
    protected function createAccountingEntry(Invoice $invoice, float $costOfGoods = 0): void
    {
        $totalAmount = (float) $invoice->total_amount;
        $taxAmount = (float) $invoice->tax_amount;
        $netAmount = $totalAmount - $taxAmount;

        switch ($invoice->type) {
            case 'sale':
                $this->accountingService->recordSaleEntry(
                    tenantId: $invoice->tenant_id,
                    totalAmount: $totalAmount,
                    taxAmount: $taxAmount,
                    costOfGoods: $costOfGoods,
                    referenceType: 'invoice',
                    referenceId: $invoice->id,
                    userId: $invoice->created_by
                );
                break;

            case 'purchase':
                $this->accountingService->recordPurchaseEntry(
                    tenantId: $invoice->tenant_id,
                    totalAmount: $totalAmount,
                    taxAmount: $taxAmount,
                    referenceType: 'invoice',
                    referenceId: $invoice->id,
                    userId: $invoice->created_by
                );
                break;

            case 'sale_return':
                // برگشت از فروش: عکس فروش
                $this->accountingService->createJournalEntry(
                    tenantId: $invoice->tenant_id,
                    lines: [
                        ['account_code' => '4102', 'debit' => $netAmount, 'credit' => 0, 'description' => 'برگشت از فروش'],
                        ['account_code' => '210401', 'debit' => $taxAmount, 'credit' => 0, 'description' => 'برگشت مالیات'],
                        ['account_code' => '110201', 'debit' => 0, 'credit' => $totalAmount, 'description' => 'کاهش طلب از مشتری'],
                        ['account_code' => '110301', 'debit' => $costOfGoods, 'credit' => 0, 'description' => 'افزایش موجودی'],
                        ['account_code' => '5101', 'debit' => 0, 'credit' => $costOfGoods, 'description' => 'برگشت بهای تمام‌شده'],
                    ],
                    description: "برگشت از فروش {$invoice->number}",
                    referenceType: 'invoice',
                    referenceId: $invoice->id,
                    userId: $invoice->created_by,
                    autoApprove: true
                );
                break;

            case 'purchase_return':
                // برگشت به تأمین‌کننده
                $this->accountingService->createJournalEntry(
                    tenantId: $invoice->tenant_id,
                    lines: [
                        ['account_code' => '210101', 'debit' => $totalAmount, 'credit' => 0, 'description' => 'کاهش بدهی به تأمین‌کننده'],
                        ['account_code' => '110301', 'debit' => 0, 'credit' => $netAmount, 'description' => 'کاهش موجودی'],
                        ['account_code' => '1105', 'debit' => 0, 'credit' => $taxAmount, 'description' => 'کاهش مالیات خرید'],
                    ],
                    description: "برگشت به تأمین‌کننده {$invoice->number}",
                    referenceType: 'invoice',
                    referenceId: $invoice->id,
                    userId: $invoice->created_by,
                    autoApprove: true
                );
                break;
        }
    }

    /**
     * به‌روزرسانی موجودی حساب مشتری/تأمین‌کننده
     */
    protected function updatePartyBalance(Invoice $invoice): void
    {
        $party = $invoice->party;
        $totalAmount = (float) $invoice->total_amount;

        $change = match ($invoice->type) {
            'sale' => $totalAmount,                // مشتری بدهکار می‌شود
            'sale_return' => -$totalAmount,        // کاهش طلب
            'purchase' => -$totalAmount,           // ما بدهکار می‌شویم
            'purchase_return' => $totalAmount,     // کاهش بدهی
            default => 0,
        };

        $party->increment('balance', $change);
    }

    /**
     * لغو فاکتور (برگشت اثرات)
     */
    public function cancelInvoice(Invoice $invoice, ?int $userId = null): Invoice
    {
        if ($invoice->status === 'cancelled') {
            throw new InvalidArgumentException('فاکتور قبلاً لغو شده است.');
        }

        if ($invoice->status !== 'confirmed') {
            // فقط تغییر وضعیت
            $invoice->update(['status' => 'cancelled']);
            return $invoice->fresh();
        }

        return DB::transaction(function () use ($invoice, $userId) {
            $invoice->load('items.product', 'party');

            // ۱. برگشت اثر انبار
            $this->reverseInventory($invoice);

            // ۲. برگشت سند حسابداری (ثبت سند معکوس)
            $this->accountingService->createJournalEntry(
                tenantId: $invoice->tenant_id,
                lines: $this->getReverseEntryLines($invoice),
                description: "لغو فاکتور {$invoice->number}",
                referenceType: 'invoice_cancel',
                referenceId: $invoice->id,
                userId: $userId,
                autoApprove: true
            );

            // ۳. برگشت موجودی طرف حساب
            $this->reversePartyBalance($invoice);

            // ۴. تغییر وضعیت
            $invoice->update(['status' => 'cancelled']);

            return $invoice->fresh(['items.product', 'party']);
        });
    }

    /**
     * برگشت اثر انبار در لغو فاکتور
     */
    protected function reverseInventory(Invoice $invoice): void
    {
        $userId = $invoice->created_by;

        foreach ($invoice->items as $item) {
            $product = $item->product;
            if (!$product || !$product->track_stock) continue;

            // عکس عملیات اولیه
            if (in_array($invoice->type, ['sale', 'purchase_return'])) {
                // قبلاً خروج انجام شده، حالا ورود
                $stock = \App\Models\InventoryStock::where('warehouse_id', $invoice->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                $unitCost = $stock ? (float) $stock->average_cost : (float) $product->purchase_price;

                $this->inventoryService->increaseStock(
                    tenantId: $invoice->tenant_id,
                    warehouseId: $invoice->warehouse_id,
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    unitCost: $unitCost,
                    referenceType: 'invoice_cancel',
                    referenceId: $invoice->id,
                    description: "لغو فاکتور {$invoice->number}",
                    userId: $userId
                );
            } else {
                // purchase یا sale_return - قبلاً ورود انجام شده، حالا خروج
                $this->inventoryService->decreaseStock(
                    tenantId: $invoice->tenant_id,
                    warehouseId: $invoice->warehouse_id,
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    referenceType: 'invoice_cancel',
                    referenceId: $invoice->id,
                    description: "لغو فاکتور {$invoice->number}",
                    userId: $userId
                );
            }
        }
    }

    /**
     * خطوط سند معکوس برای لغو
     */
    protected function getReverseEntryLines(Invoice $invoice): array
    {
        $totalAmount = (float) $invoice->total_amount;
        $taxAmount = (float) $invoice->tax_amount;
        $netAmount = $totalAmount - $taxAmount;

        return match ($invoice->type) {
            'sale' => [
                ['account_code' => '4101', 'debit' => $netAmount, 'credit' => 0],
                ['account_code' => '210401', 'debit' => $taxAmount, 'credit' => 0],
                ['account_code' => '110201', 'debit' => 0, 'credit' => $totalAmount],
            ],
            'purchase' => [
                ['account_code' => '210101', 'debit' => $totalAmount, 'credit' => 0],
                ['account_code' => '110301', 'debit' => 0, 'credit' => $netAmount],
                ['account_code' => '1105', 'debit' => 0, 'credit' => $taxAmount],
            ],
            default => [],
        };
    }

    /**
     * برگشت موجودی طرف حساب
     */
    protected function reversePartyBalance(Invoice $invoice): void
    {
        $party = $invoice->party;
        $totalAmount = (float) $invoice->total_amount;

        $change = match ($invoice->type) {
            'sale' => -$totalAmount,
            'sale_return' => $totalAmount,
            'purchase' => $totalAmount,
            'purchase_return' => -$totalAmount,
            default => 0,
        };

        $party->increment('balance', $change);
    }

    /**
     * تولید شماره فاکتور
     */
    protected function generateInvoiceNumber(int $tenantId, string $type): string
    {
        $prefix = match ($type) {
            'sale' => 'S',
            'purchase' => 'P',
            'sale_return' => 'SR',
            'purchase_return' => 'PR',
        };

        $lastInvoice = Invoice::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        // استخراج آخرین بخش شماره (بعد از آخرین خط تیره)
        $nextNumber = 1;
        if ($lastInvoice) {
            $parts = explode('-', $lastInvoice->number);
            $nextNumber = ((int) end($parts)) + 1;
        }

        return $prefix . '-' . now()->format('Ym') . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
