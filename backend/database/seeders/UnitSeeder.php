<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->createUnitsForTenant($tenant->id);
        }
    }

    public static function createUnitsForTenant(int $tenantId): void
    {
        $units = [
            ['name' => 'عدد', 'symbol' => 'عدد', 'conversion_factor' => 1],
            ['name' => 'کیلوگرم', 'symbol' => 'kg', 'conversion_factor' => 1],
            ['name' => 'گرم', 'symbol' => 'g', 'conversion_factor' => 0.001],
            ['name' => 'تن', 'symbol' => 'ton', 'conversion_factor' => 1000],
            ['name' => 'متر', 'symbol' => 'm', 'conversion_factor' => 1],
            ['name' => 'سانتی‌متر', 'symbol' => 'cm', 'conversion_factor' => 0.01],
            ['name' => 'لیتر', 'symbol' => 'L', 'conversion_factor' => 1],
            ['name' => 'میلی‌لیتر', 'symbol' => 'ml', 'conversion_factor' => 0.001],
            ['name' => 'بسته', 'symbol' => 'بسته', 'conversion_factor' => 1],
            ['name' => 'کارتن', 'symbol' => 'کارتن', 'conversion_factor' => 1],
            ['name' => 'جعبه', 'symbol' => 'جعبه', 'conversion_factor' => 1],
            ['name' => 'شعله', 'symbol' => 'شعله', 'conversion_factor' => 1],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => $unit['name']],
                array_merge($unit, ['tenant_id' => $tenantId, 'is_active' => true])
            );
        }
    }
}
