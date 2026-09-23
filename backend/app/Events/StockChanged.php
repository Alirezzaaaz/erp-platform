<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $productId,
        public int $warehouseId,
        public float $newQuantity,
        public string $action = 'update'
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("tenant.{$this->tenantId}.inventory")];
    }

    public function broadcastAs(): string
    {
        return 'stock.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'new_quantity' => $this->newQuantity,
            'action' => $this->action,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
