<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseLayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'version' => $this->version,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'draft' => 'پیش‌نویس',
                'published' => 'منتشر شده',
                'archived' => 'آرشیو شده',
                default => $this->status,
            },
            'total_width' => (float) $this->total_width,
            'total_height' => (float) $this->total_height,
            'unit_of_measure' => $this->unit_of_measure,
            'layout_data' => $this->layout_data,
            'notes' => $this->notes,

            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
                'code' => $this->warehouse->code,
            ]),

            'locations' => $this->whenLoaded('locations', fn () => WarehouseLocationResource::collection($this->locations)),

            'locations_count' => $this->when(
                !$this->relationLoaded('locations'),
                fn () => $this->locations()->count()
            ),

            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),

            'publisher' => $this->whenLoaded('publisher', fn () => $this->publisher ? [
                'id' => $this->publisher->id,
                'name' => $this->publisher->name,
            ] : null),

            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
