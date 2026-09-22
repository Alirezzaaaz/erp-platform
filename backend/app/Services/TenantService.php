<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantTheme;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\FiscalYear;
use App\Database\Seeders\RoleSeeder;
use App\Database\Seeders\UnitSeeder;
use App\Database\Seeders\AccountSeeder;
use App\Database\Seeders\WarehouseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantService
{
    /**
     * ایجاد یک مستاجر جدید همراه با داده‌های اولیه
     */
    public function createTenant(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            // ۱. ساخت مستاجر
            $tenant = Tenant::create([
                'name' => $data['company_name'],
                'subdomain' => $data['subdomain'],
                'business_type' => $data['business_type'],
                'size_category' => $data['size_category'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => true,
                'trial_ends_at' => now()->addDays(14),
            ]);

            // ۲. ساخت تم پیش‌فرض
            TenantTheme::create([
                'tenant_id' => $tenant->id,
                'primary_color' => '#1976D2',
                'secondary_color' => '#424242',
                'font_family' => 'Vazirmatn',
                'border_radius' => 8,
                'theme_mode' => 'light',
            ]);

            // ۳. ساخت داده‌های اولیه
            RoleSeeder::createRolesForTenant($tenant->id);
            UnitSeeder::createUnitsForTenant($tenant->id);
            AccountSeeder::createAccountsForTenant($tenant->id);
            WarehouseSeeder::createDefaultWarehouseForTenant($tenant->id);
            $this->createCurrentFiscalYear($tenant->id);

            return $tenant->fresh();
        });
    }

    /**
     * ساخت سال مالی جاری برای مستاجر
     */
    protected function createCurrentFiscalYear(int $tenantId): void
    {
        $currentYear = (int) now()->format('Y');

        FiscalYear::create([
            'tenant_id' => $tenantId,
            'name' => "سال مالی {$currentYear}",
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
        ]);
    }

    /**
     * یافتن مستاجر بر اساس زیردامنه
     */
    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return Tenant::where('subdomain', $subdomain)
            ->where('is_active', true)
            ->first();
    }
}
