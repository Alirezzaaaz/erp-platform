<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseApiController
{
    /**
     * لیست کالاها با فیلتر و جستجو
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId();

        $query = Product::query()
            ->where('tenant_id', $tenantId)
            ->with(['category', 'unit']);

        // فیلتر جستجو (کد، بارکد، نام)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // فیلتر دسته‌بندی
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // فیلتر واحد
        if ($unitId = $request->input('unit_id')) {
            $query->where('unit_id', $unitId);
        }

        // فیلتر وضعیت فعال
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // فیلتر کالاهای زیر نقطه سفارش
        if ($request->boolean('below_reorder')) {
            $query->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM inventory_stocks WHERE product_id = products.id) <= products.reorder_point');
        }

        // مرتب‌سازی
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['id', 'code', 'name', 'sale_price', 'purchase_price', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        // صفحه‌بندی
        $perPage = min((int) $request->input('per_page', 20), 100);
        $products = $query->paginate($perPage);

        return $this->collectionResponse(
            ProductResource::collection($products),
            'لیست کالاها با موفقیت دریافت شد.'
        );
    }

    /**
     * ثبت کالای جدید
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $tenantId = $this->getTenantId();

        $product = DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();
            $data['tenant_id'] = $tenantId;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['track_stock'] = $data['track_stock'] ?? true;

            return Product::create($data);
        });

        $product->load(['category', 'unit']);

        return $this->successResponse(
            new ProductResource($product),
            'کالا با موفقیت ثبت شد.',
            201
        );
    }

    /**
     * نمایش جزئیات کالا
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorizeProduct($product);

        $product->load(['category', 'unit', 'stocks.warehouse']);

        return $this->successResponse(
            new ProductResource($product),
            'جزئیات کالا با موفقیت دریافت شد.'
        );
    }

    /**
     * ویرایش کالا
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($product);

        DB::transaction(function () use ($request, $product) {
            $product->update($request->validated());
        });

        $product->load(['category', 'unit']);

        return $this->successResponse(
            new ProductResource($product->fresh(['category', 'unit'])),
            'کالا با موفقیت ویرایش شد.'
        );
    }

    /**
     * حذف کالا (Soft Delete)
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorizeProduct($product);

        // بررسی اینکه کالا در فاکتور استفاده نشده باشد
        $usedInInvoices = DB::table('invoice_items')
            ->where('product_id', $product->id)
            ->exists();

        if ($usedInInvoices) {
            return $this->errorResponse(
                'این کالا در فاکتورها استفاده شده و قابل حذف نیست. می‌توانید آن را غیرفعال کنید.',
                422
            );
        }

        $product->delete();

        return $this->successResponse(
            null,
            'کالا با موفقیت حذف شد.'
        );
    }

    /**
     * فعال/غیرفعال کردن کالا
     */
    public function toggleActive(Product $product): JsonResponse
    {
        $this->authorizeProduct($product);

        $product->update(['is_active' => !$product->is_active]);

        return $this->successResponse(
            new ProductResource($product->fresh(['category', 'unit'])),
            $product->is_active ? 'کالا فعال شد.' : 'کالا غیرفعال شد.'
        );
    }

    /**
     * بررسی اینکه کالا به مستاجر جاری تعلق دارد
     */
    protected function authorizeProduct(Product $product): void
    {
        if ($product->tenant_id !== $this->getTenantId()) {
            abort(404, 'کالا یافت نشد.');
        }
    }
}
