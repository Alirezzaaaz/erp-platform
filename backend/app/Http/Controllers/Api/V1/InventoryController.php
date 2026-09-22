<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Inventory\AdjustRequest;
use App\Http\Requests\Inventory\StockInRequest;
use App\Http\Requests\Inventory\StockOutRequest;
use App\Http\Requests\Inventory\TransferRequest;
use App\Http\Resources\InventoryStockResource;
use App\Http\Resources\InventoryTransactionResource;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class InventoryController extends BaseApiController
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * لیست تراکنش‌های انبار
     */
    public function transactions(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId();

        $query = InventoryTransaction::query()
            ->where('tenant_id', $tenantId)
            ->with(['warehouse:id,name,code', 'product:id,code,name', 'creator:id,name']);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($from = $request->input('from_date')) {
            $query->where('transaction_date', '>=', $from);
        }

        if ($to = $request->input('to_date')) {
            $query->where('transaction_date', '<=', $to);
        }

        $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');

        $perPage = min((int) $request->input('per_page', 30), 100);
        $transactions = $query->paginate($perPage);

        return $this->collectionResponse(
            InventoryTransactionResource::collection($transactions),
            'لیست تراکنش‌ها دریافت شد.'
        );
    }

    /**
     * ثبت ورود کالا
     */
    public function stockIn(StockInRequest $request): JsonResponse
    {
        try {
            $tx = $this->inventoryService->increaseStock(
                tenantId: $this->getTenantId(),
                warehouseId: (int) $request->warehouse_id,
                productId: (int) $request->product_id,
                quantity: (float) $request->quantity,
                unitCost: (float) $request->unit_cost,
                referenceType: $request->reference_type,
                referenceId: $request->reference_id,
                description: $request->description,
            );

            $tx->load(['warehouse', 'product', 'creator']);

            return $this->successResponse(
                new InventoryTransactionResource($tx),
                'ورود کالا با موفقیت ثبت شد.',
                201
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * ثبت خروج کالا
     */
    public function stockOut(StockOutRequest $request): JsonResponse
    {
        try {
            $tx = $this->inventoryService->decreaseStock(
                tenantId: $this->getTenantId(),
                warehouseId: (int) $request->warehouse_id,
                productId: (int) $request->product_id,
                quantity: (float) $request->quantity,
                referenceType: $request->reference_type,
                referenceId: $request->reference_id,
                description: $request->description,
            );

            $tx->load(['warehouse', 'product', 'creator']);

            return $this->successResponse(
                new InventoryTransactionResource($tx),
                'خروج کالا با موفقیت ثبت شد.',
                201
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * انتقال بین انبارها
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            $result = $this->inventoryService->transferStock(
                tenantId: $this->getTenantId(),
                fromWarehouseId: (int) $request->from_warehouse_id,
                toWarehouseId: (int) $request->to_warehouse_id,
                productId: (int) $request->product_id,
                quantity: (float) $request->quantity,
                description: $request->description,
            );

            return $this->successResponse(
                [
                    'out' => new InventoryTransactionResource($result['out']->load(['warehouse', 'product'])),
                    'in' => new InventoryTransactionResource($result['in']->load(['warehouse', 'product'])),
                ],
                'انتقال با موفقیت انجام شد.',
                201
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * تعدیل موجودی
     */
    public function adjust(AdjustRequest $request): JsonResponse
    {
        try {
            $tx = $this->inventoryService->adjustStock(
                tenantId: $this->getTenantId(),
                warehouseId: (int) $request->warehouse_id,
                productId: (int) $request->product_id,
                newQuantity: (float) $request->new_quantity,
                description: $request->description,
            );

            if (!$tx) {
                return $this->successResponse(null, 'موجودی تغییری نیافت.');
            }

            return $this->successResponse(
                new InventoryTransactionResource($tx->load(['warehouse', 'product'])),
                'تعدیل با موفقیت انجام شد.',
                201
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * لیست موجودی انبارها
     */
    public function stockList(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId();

        $query = InventoryStock::query()
            ->where('tenant_id', $tenantId)
            ->with(['warehouse:id,name,code', 'product:id,code,name,barcode,unit_id,reorder_point', 'product.unit:id,symbol']);

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        // فقط کالاهای با موجودی
        if ($request->boolean('only_available')) {
            $query->where('quantity', '>', 0);
        }

        // جستجو در کالا
        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $query->orderBy('warehouse_id')->orderBy('product_id');

        $perPage = min((int) $request->input('per_page', 30), 100);
        $stocks = $query->paginate($perPage);

        return $this->collectionResponse(
            InventoryStockResource::collection($stocks),
            'لیست موجودی دریافت شد.'
        );
    }

    /**
     * گزارش موجودی یک کالا در همه انبارها
     */
    public function productStock(Request $request, int $productId): JsonResponse
    {
        $data = $this->inventoryService->getProductTotalStock(
            $this->getTenantId(),
            $productId
        );

        return $this->successResponse($data, 'گزارش موجودی کالا دریافت شد.');
    }

    /**
     * خلاصه موجودی کل انبارها
     */
    public function summary(): JsonResponse
    {
        $tenantId = $this->getTenantId();

        $totalStocks = InventoryStock::where('tenant_id', $tenantId)->count();
        $totalQuantity = InventoryStock::where('tenant_id', $tenantId)->sum('quantity');
        $totalValue = InventoryStock::where('tenant_id', $tenantId)
            ->selectRaw('SUM(quantity * average_cost) as total')
            ->value('total') ?? 0;

        // کالاهای زیر نقطه سفارش
        $belowReorder = InventoryStock::where('tenant_id', $tenantId)
            ->whereHas('product', function ($q) {
                $q->whereRaw('inventory_stocks.quantity <= products.reorder_point');
            })
            ->count();

        return $this->successResponse([
            'total_stock_records' => $totalStocks,
            'total_quantity' => (float) $totalQuantity,
            'total_value' => (float) $totalValue,
            'below_reorder_count' => $belowReorder,
        ], 'خلاصه موجودی دریافت شد.');
    }
}
