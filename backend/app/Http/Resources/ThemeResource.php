<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThemeResource extends JsonResource
{
    public function toArray(Request $request): array
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
                'borderRadius' => (int) $this->border_radius,
                'logoUrl' => $this->logo_url,
                'faviconUrl' => $this->favicon_url,
                'themeMode' => $this->theme_mode,
                'customTokens' => $this->custom_tokens ?? [],
            ],

            'layout' => $this->layout_config ?? $this->defaultLayout(),

            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * چیدمان پیش‌فرض
     */
    protected function defaultLayout(): array
    {
        return [
            'sidebar' => [
                ['key' => 'dashboard', 'label' => 'داشبورد', 'icon' => 'home'],
                ['key' => 'products', 'label' => 'کالاها', 'icon' => 'package'],
                ['key' => 'inventory', 'label' => 'انبار', 'icon' => 'warehouse'],
                ['key' => 'invoices', 'label' => 'فاکتورها', 'icon' => 'file-text'],
                ['key' => 'parties', 'label' => 'اشخاص', 'icon' => 'users'],
                ['key' => 'accounting', 'label' => 'حسابداری', 'icon' => 'calculator'],
                ['key' => 'reports', 'label' => 'گزارش‌ها', 'icon' => 'chart-bar'],
                ['key' => 'settings', 'label' => 'تنظیمات', 'icon' => 'settings'],
            ],
            'dashboard_widgets' => [
                ['type' => 'KpiCard', 'title' => 'فروش امروز', 'value_key' => 'sales_today', 'color' => 'primary'],
                ['type' => 'KpiCard', 'title' => 'موجودی کل', 'value_key' => 'total_stock', 'color' => 'success'],
                ['type' => 'KpiCard', 'title' => 'سفارشات در انتظار', 'value_key' => 'pending_invoices', 'color' => 'warning'],
                ['type' => 'KpiCard', 'title' => 'هشدار موجودی', 'value_key' => 'low_stock_count', 'color' => 'error'],
            ],
        ];
    }
}
