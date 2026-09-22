<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,

            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'code' => $this->category->code,
            ]),

            'unit' => $this->whenLoaded('unit', fn () => [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
                'symbol' => $this->unit->symbol,
            ]),

            'pricing' => [
                'purchase_price' => (float) $this->purchase_price,
                'sale_price' => (float) $this->sale_price,
            ],

            'stock_settings' => [
                'min_stock' => (float) $this->min_stock,
                'max_stock' => $this->max_stock ? (float) $this->max_stock : null,
                'reorder_point' => (float) $this->reorder_point,
                'track_stock' => (bool) $this->track_stock,
            ],

            'total_quantity' => $this->when(
                $request->has('include_stock') || $request->routeIs('*.show'),
                fn () => $this->totalQuantity()
            ),

            'is_below_reorder_point' => $this->when(
                $request->has('include_stock') || $request->routeIs('*.show'),
                fn () => $this->isBelowReorderPoint()
            ),

            'image_url' => $this->image_url,
            'attributes' => $this->attributes,
            'is_active' => (bool) $this->is_active,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
