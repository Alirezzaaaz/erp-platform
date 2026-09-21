<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'warehouse_layout_id',
        'code',
        'type',
        'name',
        'pos_x',
        'pos_y',
        'pos_z',
        'width',
        'depth',
        'height',
        'capacity',
        'metadata',
    ];

    protected $casts = [
        'pos_x' => 'decimal:2',
        'pos_y' => 'decimal:2',
        'pos_z' => 'decimal:2',
        'width' => 'decimal:2',
        'depth' => 'decimal:2',
        'height' => 'decimal:2',
        'capacity' => 'integer',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(WarehouseLayout::class, 'warehouse_layout_id');
    }
}
