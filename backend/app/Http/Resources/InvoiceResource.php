<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'number' => $this->number,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'payment_status' => $this->payment_status,

            'party' => $this->whenLoaded('party', fn () => [
                'id' => $this->party->id,
                'code' => $this->party->code,
                'name' => $this->party->name,
                'company_name' => $this->party->company_name,
                'mobile' => $this->party->mobile,
            ]),

            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ] : null),

            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),

            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'remaining_amount' => $this->remainingAmount(),

            'tax_info' => [
                'tax_id' => $this->tax_id,
                'tax_reference_id' => $this->tax_reference_id,
                'tax_status' => $this->tax_status,
            ],

            'reference_number' => $this->reference_number,
            'notes' => $this->notes,

            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_code' => $item->product?->code,
                'product_name' => $item->product?->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_percent' => (float) $item->discount_percent,
                'discount_amount' => (float) $item->discount_amount,
                'tax_percent' => (float) $item->tax_percent,
                'tax_amount' => (float) $item->tax_amount,
                'total_amount' => (float) $item->total_amount,
                'description' => $item->description,
            ])),

            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function getTypeLabel(): string
    {
        return match ($this->type) {
            'sale' => 'فروش',
            'purchase' => 'خرید',
            'sale_return' => 'برگشت از فروش',
            'purchase_return' => 'برگشت به تأمین‌کننده',
            default => $this->type,
        };
    }

    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'پیش‌نویس',
            'confirmed' => 'تایید شده',
            'cancelled' => 'لغو شده',
            default => $this->status,
        };
    }
}
