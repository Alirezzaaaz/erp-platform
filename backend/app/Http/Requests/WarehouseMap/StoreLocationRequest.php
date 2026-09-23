<?php

namespace App\Http\Requests\WarehouseMap;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:zone,aisle,rack,shelf,bin'],
            'name' => ['required', 'string', 'max:255'],
            'pos_x' => ['nullable', 'numeric', 'min:0'],
            'pos_y' => ['nullable', 'numeric', 'min:0'],
            'pos_z' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0.1'],
            'depth' => ['nullable', 'numeric', 'min:0.1'],
            'height' => ['nullable', 'numeric', 'min:0.1'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'کد موقعیت الزامی است.',
            'type.required' => 'نوع موقعیت الزامی است.',
            'name.required' => 'نام موقعیت الزامی است.',
        ];
    }
}
