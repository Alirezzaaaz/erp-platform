<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            self::createDefaultWarehouseForTenant($tenant->id, $tenant->address);
        }
    }

    public static function createDefaultWarehouseForTenant(int $tenantId, ?string $address = null): Warehouse
    {
        return Warehouse::updateOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'MAIN'],
            [
                'tenant_id' => $tenantId,
                'name' => 'انبار اصلی',
                'code' => 'MAIN',
                'address' => $address,
                'is_active' => true,
                'is_default' => true,
            ]
        );
    }
}
