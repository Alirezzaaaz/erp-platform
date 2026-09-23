<?php

namespace App\Http\Requests\WarehouseMap;

use Illuminate\Foundation\Http\FormRequest;

class BulkLocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'locations' => ['required', 'array', 'min:1', 'max:500'],
            'locations.*.code' => ['required', 'string', 'max:50'],
            'locations.*.type' => ['required', 'in:zone,aisle,rack,shelf,bin'],
            'locations.*.name' => ['required', 'string', 'max:255'],
            'locations.*.pos_x' => ['nullable', 'numeric', 'min:0'],
            'locations.*.pos_y' => ['nullable', 'numeric', 'min:0'],
            'locations.*.width' => ['nullable', 'numeric', 'min:0.1'],
            'locations.*.depth' => ['nullable', 'numeric', 'min:0.1'],
            'locations.*.height' => ['nullable', 'numeric', 'min:0.1'],
            'locations.*.capacity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
