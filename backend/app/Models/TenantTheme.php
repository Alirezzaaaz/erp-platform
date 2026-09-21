<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'primary_color',
        'secondary_color',
        'success_color',
        'warning_color',
        'error_color',
        'background_color',
        'font_family',
        'border_radius',
        'logo_url',
        'favicon_url',
        'theme_mode',
        'layout_config',
        'custom_tokens',
        'version',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'custom_tokens' => 'array',
        'border_radius' => 'integer',
        'version' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function toApiArray(): array
    {
        return [
            'version' => $this->version,
            'theme' => [
                'primaryColor' => $this->primary_color,
                'secondaryColor' => $this->secondary_color,
                'successColor' => $this->success_color,
                'warningColor' => $this->warning_color,
                'errorColor' => $this->error_color,
                'backgroundColor' => $this->background_color,
                'fontFamily' => $this->font_family,
                'borderRadius' => $this->border_radius,
                'logoUrl' => $this->logo_url,
                'faviconUrl' => $this->favicon_url,
                'themeMode' => $this->theme_mode,
                'customTokens' => $this->custom_tokens,
            ],
            'layout' => $this->layout_config,
        ];
    }
}
