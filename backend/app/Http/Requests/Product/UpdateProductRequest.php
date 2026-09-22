<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) auth('api')->payload()->get('tenant_id');
        $productId = $this->route('product');

        return [
            'code' => [
                'sometimes', 'string', 'max:100',
                Rule::unique('products', 'code')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')
                    ->ignore($productId),
            ],
            'barcode' => ['nullable', 'string', 'max:100'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')->where('tenant_id', $tenantId),
            ],
            'unit_id' => [
                'sometimes',
                Rule::exists('units', 'id')->where('tenant_id', $tenantId),
            ],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0', 'gte:min_stock'],
            'reorder_point' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'attributes' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'track_stock' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'این کد کالا قبلاً ثبت شده است.',
            'unit_id.exists' => 'واحد انتخاب‌شده معتبر نیست.',
            'category_id.exists' => 'دسته‌بندی انتخاب‌شده معتبر نیست.',
        ];
    }
}
