<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'nature' => $this->nature,
            'nature_label' => $this->nature === 'debit' ? 'بدهکار' : 'بستانکار',
            'level' => $this->level,
            'parent_id' => $this->parent_id,
            'is_active' => (bool) $this->is_active,
            'is_system' => (bool) $this->is_system,
            'children' => $this->whenLoaded('children', fn () => AccountResource::collection($this->children)),
        ];
    }

    protected function getTypeLabel(): string
    {
        return match ($this->type) {
            'asset' => 'دارایی',
            'liability' => 'بدهی',
            'equity' => 'حقوق صاحبان سهام',
            'revenue' => 'درآمد',
            'expense' => 'هزینه',
            default => $this->type,
        };
    }
}
