<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
                'code' => $this->warehouse->code,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'code' => $this->product->code,
                'name' => $this->product->name,
                'barcode' => $this->product->barcode,
                'unit' => $this->product->unit?->symbol,
                'reorder_point' => (float) $this->product->reorder_point,
            ]),
            'quantity' => (float) $this->quantity,
            'reserved_quantity' => (float) $this->reserved_quantity,
            'available_quantity' => $this->availableQuantity(),
            'average_cost' => (float) $this->average_cost,
            'stock_value' => (float) $this->quantity * (float) $this->average_cost,
            'is_below_reorder' => $this->product
                ? (float) $this->quantity <= (float) $this->product->reorder_point
                : false,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
