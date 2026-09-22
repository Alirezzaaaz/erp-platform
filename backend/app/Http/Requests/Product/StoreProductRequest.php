<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) auth('api')->payload()->get('tenant_id');

        return [
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('products', 'code')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'barcode' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')->where('tenant_id', $tenantId),
            ],
            'unit_id' => [
                'required',
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
            'code.required' => 'کد کالا الزامی است.',
            'code.unique' => 'این کد کالا قبلاً ثبت شده است.',
            'name.required' => 'نام کالا الزامی است.',
            'unit_id.required' => 'واحد اندازه‌گیری الزامی است.',
            'unit_id.exists' => 'واحد انتخاب‌شده معتبر نیست.',
            'category_id.exists' => 'دسته‌بندی انتخاب‌شده معتبر نیست.',
            'sale_price.numeric' => 'قیمت فروش باید عددی باشد.',
            'purchase_price.numeric' => 'قیمت خرید باید عددی باشد.',
            'max_stock.gte' => 'حداکثر موجودی نباید کمتر از حداقل موجودی باشد.',
        ];
    }
}
