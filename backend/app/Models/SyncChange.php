<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'entity_type', 'entity_id', 'action',
        'payload', 'changed_fields', 'user_id', 'source_platform', 'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'changed_fields' => 'array',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
