<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = (int) auth('api')->payload()->get('tenant_id');
        $unitId = $this->route('unit');

        return [
            'name' => [
                'sometimes', 'string', 'max:100',
                Rule::unique('units', 'name')->where('tenant_id', $tenantId)->ignore($unitId),
            ],
            'symbol' => ['sometimes', 'string', 'max:20'],
            'conversion_factor' => ['nullable', 'numeric', 'min:0.000001'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
