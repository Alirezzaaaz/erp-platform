<?php

namespace App\Http\Requests\WarehouseMap;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLayoutRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'total_width' => ['sometimes', 'numeric', 'min:1'],
            'total_height' => ['sometimes', 'numeric', 'min:1'],
            'unit_of_measure' => ['sometimes', 'in:meter,centimeter'],
            'layout_data' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
