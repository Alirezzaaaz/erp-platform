<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = (int) auth('api')->payload()->get('tenant_id');

        return [
            'from_warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)],
            'to_warehouse_id' => ['required', 'different:from_warehouse_id', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)],
            'product_id' => ['required', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_warehouse_id.required' => 'انبار مبدأ الزامی است.',
            'to_warehouse_id.required' => 'انبار مقصد الزامی است.',
            'to_warehouse_id.different' => 'انبار مقصد باید متفاوت از انبار مبدأ باشد.',
            'quantity.gt' => 'مقدار باید بزرگ‌تر از صفر باشد.',
        ];
    }
}
