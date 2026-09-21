<?php

namespace Database\Seeders;

use App\Models\FiscalYear;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Morilog\Jalali\Jalalian;

class FiscalYearSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->createFiscalYearForTenant($tenant->id);
        }
    }

    public static function createFiscalYearForTenant(int $tenantId): void
    {
        $now = Jalalian::now();
        $currentYear = $now->getYear();

        // سال مالی ایران: از ۱ فروردین تا ۲۹/۳۰ اسفند
        $startDate = Jalalian::fromFormat('Y-m-d', ($currentYear . '-01-01'))->toCarbon();
        $endDate = Jalalian::fromFormat('Y-m-d', ($currentYear . '-12-29'))->toCarbon();

        FiscalYear::updateOrCreate(
            ['tenant_id' => $tenantId, 'name' => "سال مالی {$currentYear}"],
            [
                'tenant_id' => $tenantId,
                'name' => "سال مالی {$currentYear}",
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_closed' => false,
                'is_active' => true,
            ]
        );
    }
}
