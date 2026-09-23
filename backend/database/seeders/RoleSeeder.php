<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Tenant::all() as $tenant) {
            self::createRolesForTenant($tenant->id);
        }
    }

    public static function createRolesForTenant(int $tenantId): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'مدیر ارشد',
                'description' => 'دسترسی کامل',
                'permissions' => ['*'],
                'is_system' => true,
            ],
            [
                'name' => 'accountant',
                'display_name' => 'حسابدار',
                'description' => 'دسترسی کامل به مالی و گزارش‌ها',
                'permissions' => [
                    'dashboard.view',
                    'accounting.view', 'accounting.create', 'accounting.edit', 'accounting.delete', 'accounting.approve',
                    'reports.view', 'reports.export',
                    'invoices.view', 'invoices.create', 'invoices.edit',
                    'parties.view', 'parties.create', 'parties.edit',
                    'products.view',
                    'inventory.view',
                    'warehouses.view',
                    'warehouse_map.view',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'warehouse_designer',
                'display_name' => 'طراح انبار',
                'description' => 'دسترسی به طراحی نقشه انبار (فقط ویندوز)',
                'permissions' => [
                    'dashboard.view',
                    'products.view',
                    'inventory.view',
                    'warehouses.view',
                    'warehouse_map.view', 'warehouse_map.create', 'warehouse_map.edit', 'warehouse_map.delete',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'warehouse_operator',
                'display_name' => 'اپراتور انبار',
                'description' => 'مدیریت انبار و موجودی',
                'permissions' => [
                    'dashboard.view',
                    'products.view', 'products.create', 'products.edit',
                    'inventory.view', 'inventory.create', 'inventory.edit',
                    'warehouses.view',
                    'warehouse_map.view',
                    'invoices.view',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'viewer',
                'display_name' => 'مشاهده‌گر',
                'description' => 'فقط مشاهده',
                'permissions' => [
                    'dashboard.view',
                    'products.view',
                    'inventory.view',
                    'invoices.view',
                    'parties.view',
                    'accounting.view',
                    'reports.view', 'reports.export',
                    'warehouse_map.view',
                ],
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => $roleData['name']],
                array_merge($roleData, ['tenant_id' => $tenantId])
            );
        }
    }
}
