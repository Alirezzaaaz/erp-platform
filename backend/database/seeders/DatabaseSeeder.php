<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantTheme;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 شروع بارگذاری داده‌های اولیه...');

        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'demo'],
            [
                'name' => 'شرکت نمونه ایرانی',
                'subdomain' => 'demo',
                'business_type' => 'retail',
                'size_category' => 'medium',
                'phone' => '02112345678',
                'address' => 'تهران، خیابان آزادی',
                'is_active' => true,
            ]
        );

        $adminUser = User::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'admin@demo.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'مدیر سیستم',
                'email' => 'admin@demo.local',
                'mobile' => '09121234567',
                'password' => 'password',
                'status' => 'active',
            ]
        );

        TenantTheme::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id,
                'primary_color' => '#1976D2',
                'secondary_color' => '#424242',
                'font_family' => 'Vazirmatn',
                'border_radius' => 8,
                'theme_mode' => 'light',
            ]
        );

        $this->call([
            RoleSeeder::class,
            UnitSeeder::class,
            AccountSeeder::class,
            FiscalYearSeeder::class,
            WarehouseSeeder::class,
            ProductSeeder::class,
            PartySeeder::class,
        ]);

        $adminRole = Role::where('tenant_id', $tenant->id)->where('name', 'admin')->first();
        if ($adminRole && !$adminUser->roles()->where('role_id', $adminRole->id)->exists()) {
            $adminUser->roles()->attach($adminRole->id);
        }

        $this->command->info('✅ همه داده‌های اولیه با موفقیت بارگذاری شدند.');
        $this->command->line('🔑 Login: demo / admin@demo.local / password');
    }
}
