<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'version',
        'status',
        'layout_data',
        'total_width',
        'total_height',
        'unit_of_measure',
        'notes',
        'created_by',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'layout_data' => 'array',
        'total_width' => 'decimal:2',
        'total_height' => 'decimal:2',
        'version' => 'integer',
        'published_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function publish(int $userId): void
    {
        $this->update([
            'status' => 'published',
            'published_by' => $userId,
            'published_at' => now(),
        ]);
    }
}
