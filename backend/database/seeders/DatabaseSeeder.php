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

        // ۱. ساخت مستاجر نمونه
        $this->command->info('📦 ساخت مستاجر نمونه...');
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

        // ۲. ساخت کاربر مدیر
        $this->command->info('👤 ساخت کاربر مدیر...');
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

        // ۳. ساخت تم پیش‌فرض
        $this->command->info('🎨 ساخت تم پیش‌فرض...');
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

        // ۴. اجرای سایر Seederها
        $this->command->info('🔐 ساخت نقش‌ها...');
        $this->call(RoleSeeder::class);

        $this->command->info('📏 ساخت واحدهای اندازه‌گیری...');
        $this->call(UnitSeeder::class);

        $this->command->info('💰 ساخت کدینگ حساب‌ها...');
        $this->call(AccountSeeder::class);

        $this->command->info('📅 ساخت سال مالی...');
        $this->call(FiscalYearSeeder::class);

        $this->command->info('🏭 ساخت انبار پیش‌فرض...');
        $this->call(WarehouseSeeder::class);

        // ۵. اختصاص نقش admin به کاربر مدیر
        $this->command->info('🔗 اختصاص نقش مدیر...');
        $adminRole = Role::where('tenant_id', $tenant->id)->where('name', 'admin')->first();
        if ($adminRole && !$adminUser->roles()->where('role_id', $adminRole->id)->exists()) {
            $adminUser->roles()->attach($adminRole->id);
        }

        $this->command->info('✅ همه داده‌های اولیه با موفقیت بارگذاری شدند.');
        $this->command->newLine();
        $this->command->info('🔑 اطلاعات ورود آزمایشی:');
        $this->command->line('   Subdomain: demo');
        $this->command->line('   Email:     admin@demo.local');
        $this->command->line('   Password:  password');
    }
}
