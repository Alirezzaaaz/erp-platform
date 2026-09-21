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
            Warehouse::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'MAIN'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'انبار اصلی',
                    'code' => 'MAIN',
                    'address' => $tenant->address,
                    'is_active' => true,
                    'is_default' => true,
                ]
            );
        }
    }
}
