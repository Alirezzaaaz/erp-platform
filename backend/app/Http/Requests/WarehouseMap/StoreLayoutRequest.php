<?php

namespace App\Http\Requests\WarehouseMap;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLayoutRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = (int) auth('api')->payload()->get('tenant_id');

        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)],
            'total_width' => ['nullable', 'numeric', 'min:1', 'max:10000'],
            'total_height' => ['nullable', 'numeric', 'min:1', 'max:10000'],
            'unit_of_measure' => ['nullable', 'in:meter,centimeter'],
            'grid_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'background_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
