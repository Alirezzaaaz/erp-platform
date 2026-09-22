<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ProductCategory\StoreProductCategoryRequest;
use App\Http\Requests\ProductCategory\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = ProductCategory::query()
            ->where('tenant_id', $this->getTenantId())
            ->with('parent');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('parent_id')) {
            $parentId = $request->input('parent_id');
            if ($parentId === 'null' || $parentId === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('name');

        $perPage = min((int) $request->input('per_page', 50), 200);
        $categories = $query->paginate($perPage);

        return $this->collectionResponse(
            ProductCategoryResource::collection($categories),
            'لیست دسته‌بندی‌ها دریافت شد.'
        );
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $this->getTenantId();
        $data['is_active'] = $data['is_active'] ?? true;

        $category = ProductCategory::create($data);

        return $this->successResponse(
            new ProductCategoryResource($category),
            'دسته‌بندی با موفقیت ثبت شد.',
            201
        );
    }

    public function show(ProductCategory $productCategory): JsonResponse
    {
        $this->authorizeCategory($productCategory);
        $productCategory->load(['parent', 'children']);

        return $this->successResponse(
            new ProductCategoryResource($productCategory),
            'جزئیات دسته‌بندی دریافت شد.'
        );
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        $this->authorizeCategory($productCategory);
        $productCategory->update($request->validated());

        return $this->successResponse(
            new ProductCategoryResource($productCategory->fresh('parent')),
            'دسته‌بندی با موفقیت ویرایش شد.'
        );
    }

    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $this->authorizeCategory($productCategory);

        if ($productCategory->products()->exists()) {
            return $this->errorResponse(
                'این دسته‌بندی دارای کالا است و قابل حذف نیست.',
                422
            );
        }

        if ($productCategory->children()->exists()) {
            return $this->errorResponse(
                'این دسته‌بندی دارای زیرمجموعه است و قابل حذف نیست.',
                422
            );
        }

        $productCategory->delete();

        return $this->successResponse(null, 'دسته‌بندی با موفقیت حذف شد.');
    }

    protected function authorizeCategory(ProductCategory $category): void
    {
        if ($category->tenant_id !== $this->getTenantId()) {
            abort(404, 'دسته‌بندی یافت نشد.');
        }
    }
}
