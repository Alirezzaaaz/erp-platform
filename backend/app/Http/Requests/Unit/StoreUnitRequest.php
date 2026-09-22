<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = (int) auth('api')->payload()->get('tenant_id');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('units', 'name')->where('tenant_id', $tenantId),
            ],
            'symbol' => ['required', 'string', 'max:20'],
            'conversion_factor' => ['nullable', 'numeric', 'min:0.000001'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام واحد الزامی است.',
            'name.unique' => 'این واحد قبلاً ثبت شده است.',
            'symbol.required' => 'نماد واحد الزامی است.',
        ];
    }
}
