<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * ورود کالا به انبار (خرید، برگشت از فروش، تعدیل مثبت)
     */
    public function increaseStock(
        int $tenantId,
        int $warehouseId,
        int $productId,
        float $quantity,
        float $unitCost,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        ?int $userId = null
    ): InventoryTransaction {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('مقدار ورودی باید بزرگ‌تر از صفر باشد.');
        }

        return DB::transaction(function () use (
            $tenantId, $warehouseId, $productId, $quantity, $unitCost,
            $referenceType, $referenceId, $description, $userId
        ) {
            $stock = $this->getOrCreateStock($tenantId, $warehouseId, $productId);

            $oldQty = (float) $stock->quantity;
            $oldAvg = (float) $stock->average_cost;

            // محاسبه میانگین موزون جدید
            $newQty = $oldQty + $quantity;
            $newAvg = $newQty > 0
                ? (($oldQty * $oldAvg) + ($quantity * $unitCost)) / $newQty
                : $unitCost;

            $stock->update([
                'quantity' => $newQty,
                'average_cost' => round($newAvg, 4),
            ]);

            return InventoryTransaction::create([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'balance_after' => $newQty,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_by' => $userId ?? auth('api')->id(),
                'transaction_date' => now(),
            ]);
        });
    }

    /**
     * خروج کالا از انبار (فروش، برگشت از خرید، تعدیل منفی)
     */
    public function decreaseStock(
        int $tenantId,
        int $warehouseId,
        int $productId,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        ?int $userId = null
    ): InventoryTransaction {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('مقدار خروجی باید بزرگ‌تر از صفر باشد.');
        }

        return DB::transaction(function () use (
            $tenantId, $warehouseId, $productId, $quantity,
            $referenceType, $referenceId, $description, $userId
        ) {
            $stock = $this->getOrCreateStock($tenantId, $warehouseId, $productId);

            $oldQty = (float) $stock->quantity;
            $currentAvg = (float) $stock->average_cost;

            if ($oldQty < $quantity) {
                throw new InvalidArgumentException(
                    "موجودی کافی نیست. موجودی فعلی: {$oldQty}، درخواست: {$quantity}"
                );
            }

            $newQty = $oldQty - $quantity;
            // میانگین موزون با خروج تغییر نمی‌کند
            $totalCost = $quantity * $currentAvg;

            $stock->update([
                'quantity' => $newQty,
                // average_cost تغییر نمی‌کند
            ]);

            return InventoryTransaction::create([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => $quantity,
                'unit_cost' => $currentAvg,
                'total_cost' => $totalCost,
                'balance_after' => $newQty,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_by' => $userId ?? auth('api')->id(),
                'transaction_date' => now(),
            ]);
        });
    }

    /**
     * انتقال کالا بین انبارها
     */
    public function transferStock(
        int $tenantId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $productId,
        float $quantity,
        ?string $description = null,
        ?int $userId = null
    ): array {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new InvalidArgumentException('انبار مبدأ و مقصد نباید یکسان باشند.');
        }

        return DB::transaction(function () use (
            $tenantId, $fromWarehouseId, $toWarehouseId, $productId,
            $quantity, $description, $userId
        ) {
            // خروج از انبار مبدأ
            $outTx = $this->decreaseStock(
                $tenantId, $fromWarehouseId, $productId, $quantity,
                'transfer', null, $description ?? 'انتقال بین انبارها', $userId
            );

            // ورود به انبار مقصد با همان بهای تمام‌شده
            $inTx = $this->increaseStock(
                $tenantId, $toWarehouseId, $productId, $quantity, (float) $outTx->unit_cost,
                'transfer', null, $description ?? 'انتقال بین انبارها', $userId
            );

            return ['out' => $outTx, 'in' => $inTx];
        });
    }

    /**
     * تعدیل موجودی (اصلاح به مقدار مشخص)
     */
    public function adjustStock(
        int $tenantId,
        int $warehouseId,
        int $productId,
        float $newQuantity,
        ?string $description = null,
        ?int $userId = null
    ): ?InventoryTransaction {
        $stock = $this->getOrCreateStock($tenantId, $warehouseId, $productId);
        $oldQty = (float) $stock->quantity;
        $diff = $newQuantity - $oldQty;

        if (abs($diff) < 0.0001) {
            return null;
        }

        if ($diff > 0) {
            return $this->increaseStock(
                $tenantId, $warehouseId, $productId, $diff, (float) $stock->average_cost,
                'adjustment', null, $description ?? 'تعدیل مثبت', $userId
            );
        }

        return $this->decreaseStock(
            $tenantId, $warehouseId, $productId, abs($diff),
            'adjustment', null, $description ?? 'تعدیل منفی', $userId
        );
    }

    /**
     * دریافت یا ایجاد رکورد موجودی
     */
    protected function getOrCreateStock(int $tenantId, int $warehouseId, int $productId): InventoryStock
    {
        return InventoryStock::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
            ],
            [
                'tenant_id' => $tenantId,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'average_cost' => 0,
            ]
        );
    }

    /**
     * گزارش موجودی کل یک کالا در همه انبارها
     */
    public function getProductTotalStock(int $tenantId, int $productId): array
    {
        $stocks = InventoryStock::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->with('warehouse:id,name,code')
            ->get();

        $totalQty = $stocks->sum('quantity');
        $totalValue = $stocks->sum(fn ($s) => (float) $s->quantity * (float) $s->average_cost);

        return [
            'total_quantity' => $totalQty,
            'total_value' => $totalValue,
            'average_cost' => $totalQty > 0 ? $totalValue / $totalQty : 0,
            'warehouses' => $stocks->map(fn ($s) => [
                'warehouse_id' => $s->warehouse_id,
                'warehouse_name' => $s->warehouse->name ?? null,
                'quantity' => (float) $s->quantity,
                'average_cost' => (float) $s->average_cost,
                'value' => (float) $s->quantity * (float) $s->average_cost,
            ])->toArray(),
        ];
    }
}
