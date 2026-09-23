<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function broadcastOn(): array
    {
        return [new Channel("tenant.{$this->invoice->tenant_id}.invoices")];
    }

    public function broadcastAs(): string
    {
        return 'invoice.confirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->invoice->id,
            'number' => $this->invoice->number,
            'total_amount' => (float) $this->invoice->total_amount,
            'confirmed_at' => now()->toIso8601String(),
        ];
    }
}
