<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'entry_date' => $this->entry_date?->toDateString(),
            'description' => $this->description,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'draft' => 'پیش‌نویس',
                'approved' => 'تایید شده',
                'cancelled' => 'لغو شده',
                default => $this->status,
            },
            'total_debit' => (float) $this->lines->sum('debit'),
            'total_credit' => (float) $this->lines->sum('credit'),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id' => $line->id,
                'account_id' => $line->account_id,
                'account_code' => $line->account?->code,
                'account_name' => $line->account?->name,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'description' => $line->description,
            ])),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'approver' => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
