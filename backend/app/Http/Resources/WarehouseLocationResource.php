<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'type_label' => match ($this->type) {
                'zone' => 'منطقه',
                'aisle' => 'راهرو',
                'rack' => 'قفسه',
                'shelf' => 'طبقه',
                'bin' => 'محفظه',
                default => $this->type,
            },
            'name' => $this->name,
            'position' => [
                'x' => (float) $this->pos_x,
                'y' => (float) $this->pos_y,
                'z' => (float) $this->pos_z,
            ],
            'dimensions' => [
                'width' => (float) $this->width,
                'depth' => (float) $this->depth,
                'height' => (float) $this->height,
            ],
            'capacity' => $this->capacity,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
