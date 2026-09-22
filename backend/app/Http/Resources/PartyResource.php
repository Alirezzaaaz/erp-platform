<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'code' => $this->code,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'national_id' => $this->national_id,
            'economic_code' => $this->economic_code,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'credit_limit' => (float) $this->credit_limit,
            'balance' => (float) $this->balance,
            'is_active' => (bool) $this->is_active,
            'is_customer' => $this->isCustomer(),
            'is_supplier' => $this->isSupplier(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function getTypeLabel(): string
    {
        return match ($this->type) {
            'customer' => 'مشتری',
            'supplier' => 'تأمین‌کننده',
            'both' => 'مشتری و تأمین‌کننده',
            default => $this->type,
        };
    }
}
