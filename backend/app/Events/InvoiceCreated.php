<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function broadcastOn(): array
    {
        return [new Channel("tenant.{$this->invoice->tenant_id}.invoices")];
    }

    public function broadcastAs(): string
    {
        return 'invoice.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->invoice->id,
            'number' => $this->invoice->number,
            'type' => $this->invoice->type,
            'total_amount' => (float) $this->invoice->total_amount,
            'party_id' => $this->invoice->party_id,
            'status' => $this->invoice->status,
            'created_at' => $this->invoice->created_at?->toIso8601String(),
        ];
    }
}
