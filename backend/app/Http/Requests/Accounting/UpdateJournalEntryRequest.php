<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJournalEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entry_date' => ['sometimes', 'date'],
            'description' => ['sometimes', 'string', 'max:500'],
            'lines' => ['sometimes', 'array', 'min:2'],
            'lines.*.account_id' => ['nullable', 'integer'],
            'lines.*.account_code' => ['nullable', 'string'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
