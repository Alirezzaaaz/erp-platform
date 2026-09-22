<?php

namespace App\Events;

use App\Models\TenantTheme;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ThemeUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $tenantId,
        public TenantTheme $theme
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("tenant.{$this->tenantId}.theme"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'theme.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'version' => $this->theme->version,
            'updated_at' => $this->theme->updated_at?->toIso8601String(),
        ];
    }
}
