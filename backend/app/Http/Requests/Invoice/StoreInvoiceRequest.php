<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = (int) auth('api')->payload()->get('tenant_id');

        return [
            'type' => ['required', 'in:sale,purchase,sale_return,purchase_return'],
            'party_id' => ['required', Rule::exists('parties', 'id')->where('tenant_id', $tenantId)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'global_discount' => ['nullable', 'numeric', 'min:0'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع فاکتور الزامی است.',
            'party_id.required' => 'طرف حساب الزامی است.',
            'warehouse_id.required' => 'انبار الزامی است.',
            'items.required' => 'فاکتور باید حداقل یک ردیف داشته باشد.',
            'items.*.product_id.required' => 'کالا الزامی است.',
            'items.*.quantity.required' => 'مقدار الزامی است.',
            'items.*.quantity.gt' => 'مقدار باید بزرگ‌تر از صفر باشد.',
        ];
    }
}
