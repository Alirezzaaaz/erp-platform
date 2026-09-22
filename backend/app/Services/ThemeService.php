<?php

namespace App\Services;

use App\Events\ThemeUpdated;
use App\Models\TenantTheme;
use Illuminate\Support\Facades\Cache;

class ThemeService
{
    protected const CACHE_KEY = 'tenant_theme_';
    protected const CACHE_TTL = 3600; // 1 ساعت

    /**
     * دریافت تم مستاجر (با کش)
     */
    public function getTheme(int $tenantId): TenantTheme
    {
        return Cache::remember(
            self::CACHE_KEY . $tenantId,
            self::CACHE_TTL,
            function () use ($tenantId) {
                return TenantTheme::firstOrCreate(
                    ['tenant_id' => $tenantId],
                    $this->defaultValues($tenantId)
                );
            }
        );
    }

    /**
     * به‌روزرسانی تم
     */
    public function updateTheme(int $tenantId, array $data): TenantTheme
    {
        $theme = TenantTheme::firstOrCreate(
            ['tenant_id' => $tenantId],
            $this->defaultValues($tenantId)
        );

        // نگاشت camelCase به snake_case
        $mapping = [
            'primaryColor' => 'primary_color',
            'secondaryColor' => 'secondary_color',
            'successColor' => 'success_color',
            'warningColor' => 'warning_color',
            'errorColor' => 'error_color',
            'backgroundColor' => 'background_color',
            'fontFamily' => 'font_family',
            'borderRadius' => 'border_radius',
            'logoUrl' => 'logo_url',
            'faviconUrl' => 'favicon_url',
            'themeMode' => 'theme_mode',
            'layoutConfig' => 'layout_config',
            'customTokens' => 'custom_tokens',
        ];

        $updates = [];
        foreach ($data as $key => $value) {
            if (isset($mapping[$key])) {
                $updates[$mapping[$key]] = $value;
            }
        }

        // افزایش نسخه
        $updates['version'] = $theme->version + 1;

        $theme->update($updates);

        // پاک کردن کش
        $this->clearCache($tenantId);

        // انتشار رویداد
        event(new ThemeUpdated($tenantId, $theme->fresh()));

        return $theme->fresh();
    }

    /**
     * بازنشانی به تم پیش‌فرض
     */
    public function resetTheme(int $tenantId): TenantTheme
    {
        $theme = TenantTheme::where('tenant_id', $tenantId)->first();

        if ($theme) {
            $theme->update(array_merge(
                $this->defaultValues($tenantId),
                ['version' => $theme->version + 1]
            ));
        } else {
            $theme = TenantTheme::create($this->defaultValues($tenantId));
        }

        $this->clearCache($tenantId);
        event(new ThemeUpdated($tenantId, $theme->fresh()));

        return $theme->fresh();
    }

    /**
     * پاک کردن کش
     */
    public function clearCache(int $tenantId): void
    {
        Cache::forget(self::CACHE_KEY . $tenantId);
    }

    /**
     * مقادیر پیش‌فرض
     */
    protected function defaultValues(int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'primary_color' => '#1976D2',
            'secondary_color' => '#424242',
            'success_color' => '#2E7D32',
            'warning_color' => '#ED6C02',
            'error_color' => '#D32F2F',
            'background_color' => '#F5F5F5',
            'font_family' => 'Vazirmatn',
            'border_radius' => 8,
            'theme_mode' => 'light',
            'custom_tokens' => [],
            'version' => 1,
        ];
    }
}
