<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->createRolesForTenant($tenant->id);
        }
    }

    public static function createRolesForTenant(int $tenantId): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'مدیر ارشد',
                'description' => 'دسترسی کامل به تمام بخش‌ها و تنظیمات',
                'permissions' => ['*'],
                'is_system' => true,
            ],
            [
                'name' => 'accountant',
                'display_name' => 'حسابدار',
                'description' => 'دسترسی به بخش‌های مالی، گزارش‌گیری و اسناد',
                'permissions' => [
                    'dashboard.view',
                    'accounting.view', 'accounting.create', 'accounting.edit', 'accounting.approve',
                    'reports.view', 'reports.export',
                    'invoices.view', 'invoices.create', 'invoices.edit',
                    'parties.view', 'parties.create', 'parties.edit',
                    'products.view',
                    'inventory.view',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'warehouse_operator',
                'display_name' => 'اپراتور انبار',
                'description' => 'ثبت ورود و خروج کالا، انبارگردانی و مدیریت موجودی',
                'permissions' => [
                    'dashboard.view',
                    'inventory.view', 'inventory.create', 'inventory.edit',
                    'products.view', 'products.create', 'products.edit',
                    'warehouses.view',
                    'warehouse_map.view',
                    'invoices.view',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'viewer',
                'display_name' => 'مشاهده‌گر',
                'description' => 'فقط مشاهده و دانلود گزارش‌ها بدون امکان تغییر',
                'permissions' => [
                    'dashboard.view',
                    'inventory.view',
                    'products.view',
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
