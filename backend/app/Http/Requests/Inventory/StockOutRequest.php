<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockOutRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = (int) auth('api')->payload()->get('tenant_id');

        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)],
            'product_id' => ['required', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reference_type' => ['nullable', 'string', 'in:sale,purchase_return,adjustment,manual'],
            'reference_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'انبار الزامی است.',
            'product_id.required' => 'کالا الزامی است.',
            'quantity.required' => 'مقدار الزامی است.',
            'quantity.gt' => 'مقدار باید بزرگ‌تر از صفر باشد.',
        ];
    }
}
