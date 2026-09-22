<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncClient extends Model
{
    protected $fillable = [
        'tenant_id', 'client_id', 'device_name', 'platform',
        'last_sync_change_id', 'last_sync_at', 'app_version', 'is_active',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
