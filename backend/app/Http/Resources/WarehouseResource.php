<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'manager_name' => $this->manager_name,
            'is_active' => (bool) $this->is_active,
            'is_default' => (bool) $this->is_default,

            'total_products' => $this->when(
                $request->routeIs('*.show'),
                fn () => $this->stocks()->where('quantity', '>', 0)->count()
            ),

            'published_layout' => $this->when(
                $request->routeIs('*.show') && $this->publishedLayout(),
                fn () => [
                    'id' => $this->publishedLayout()->id,
                    'version' => $this->publishedLayout()->version,
                    'published_at' => $this->publishedLayout()->published_at?->toIso8601String(),
                ]
            ),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
