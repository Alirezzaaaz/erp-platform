<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->createAccountsForTenant($tenant->id);
        }
    }

    public static function createAccountsForTenant(int $tenantId): void
    {
        // ساختار درختی حساب‌های استاندارد ایران
        // Format: [code, name, type, nature, parent_code, level, is_system]
        $accounts = [
            // ============ دارایی‌ها ============
            ['1', 'دارایی‌ها', 'asset', 'debit', null, 1, true],
            ['11', 'دارایی‌های جاری', 'asset', 'debit', '1', 2, true],
            ['1101', 'موجودی نقد و بانک', 'asset', 'debit', '11', 3, true],
            ['110101', 'موجودی نقد', 'asset', 'debit', '1101', 4, true],
            ['110102', 'موجودی بانک‌ها', 'asset', 'debit', '1101', 4, true],
            ['1102', 'حساب‌های دریافتنی تجاری', 'asset', 'debit', '11', 3, true],
            ['110201', 'حساب‌های دریافتنی از مشتریان', 'asset', 'debit', '1102', 4, true],
            ['1103', 'موجودی مواد و کالا', 'asset', 'debit', '11', 3, true],
            ['110301', 'موجودی کالای بازرگانی', 'asset', 'debit', '1103', 4, true],
            ['110302', 'موجودی مواد اولیه', 'asset', 'debit', '1103', 4, true],
            ['110303', 'موجودی کالای در جریان ساخت', 'asset', 'debit', '1103', 4, true],
            ['110304', 'موجودی کالای ساخته شده', 'asset', 'debit', '1103', 4, true],
            ['1104', 'پیش‌پرداخت‌ها و سفارشات', 'asset', 'debit', '11', 3, true],
            ['1105', 'مالیات ارزش افزوده (خرید)', 'asset', 'debit', '11', 3, true],
            ['12', 'دارایی‌های غیرجاری', 'asset', 'debit', '1', 2, true],
            ['1201', 'دارایی‌های ثابت مشهود', 'asset', 'debit', '12', 3, true],
            ['120101', 'ساختمان', 'asset', 'debit', '1201', 4, true],
            ['120102', 'ماشین‌آلات و تجهیزات', 'asset', 'debit', '1201', 4, true],
            ['120103', 'وسایل نقلیه', 'asset', 'debit', '1201', 4, true],
            ['120104', 'اثاثه و منصوبات', 'asset', 'debit', '1201', 4, true],
            ['1202', 'استهلاک انباشته', 'asset', 'credit', '12', 3, true],

            // ============ بدهی‌ها ============
            ['2', 'بدهی‌ها', 'liability', 'credit', null, 1, true],
            ['21', 'بدهی‌های جاری', 'liability', 'credit', '2', 2, true],
            ['2101', 'حساب‌های پرداختنی تجاری', 'liability', 'credit', '21', 3, true],
            ['210101', 'حساب‌های پرداختنی به تأمین‌کنندگان', 'liability', 'credit', '2101', 4, true],
            ['2102', 'اسناد پرداختنی', 'liability', 'credit', '21', 3, true],
            ['2103', 'حقوق و دستمزد پرداختنی', 'liability', 'credit', '21', 3, true],
            ['2104', 'مالیات پرداختنی', 'liability', 'credit', '21', 3, true],
            ['210401', 'مالیات ارزش افزوده (فروش)', 'liability', 'credit', '2104', 4, true],
            ['210402', 'مالیات بر درآمد پرداختنی', 'liability', 'credit', '2104', 4, true],
            ['2105', 'بیمه پرداختنی', 'liability', 'credit', '21', 3, true],
            ['210501', 'بیمه تأمین اجتماعی', 'liability', 'credit', '2105', 4, true],
            ['2106', 'پیش‌دریافت‌ها از مشتریان', 'liability', 'credit', '21', 3, true],

            // ============ حقوق صاحبان سهام ============
            ['3', 'حقوق صاحبان سهام', 'equity', 'credit', null, 1, true],
            ['31', 'سرمایه', 'equity', 'credit', '3', 2, true],
            ['3101', 'سرمایه اولیه', 'equity', 'credit', '31', 3, true],
            ['32', 'سود و زیان انباشته', 'equity', 'credit', '3', 2, true],
            ['3201', 'سود (زیان) انباشته', 'equity', 'credit', '32', 3, true],
            ['33', 'برداشت‌ها', 'equity', 'debit', '3', 2, true],

            // ============ درآمدها ============
            ['4', 'درآمدها', 'revenue', 'credit', null, 1, true],
            ['41', 'درآمد فروش', 'revenue', 'credit', '4', 2, true],
            ['4101', 'فروش کالا', 'revenue', 'credit', '41', 3, true],
            ['4102', 'برگشت از فروش و تخفیفات', 'revenue', 'debit', '41', 3, true],
            ['410201', 'برگشت از فروش', 'revenue', 'debit', '4102', 4, true],
            ['410202', 'تخفیفات نقدی فروش', 'revenue', 'debit', '4102', 4, true],
            ['42', 'درآمدهای غیرعملیاتی', 'revenue', 'credit', '4', 2, true],
            ['4201', 'سود سپرده‌های بانکی', 'revenue', 'credit', '42', 3, true],
            ['4202', 'درآمد متفرقه', 'revenue', 'credit', '42', 3, true],

            // ============ هزینه‌ها ============
            ['5', 'هزینه‌ها', 'expense', 'debit', null, 1, true],
            ['51', 'بهای تمام‌شده کالای فروش رفته', 'expense', 'debit', '5', 2, true],
            ['5101', 'بهای تمام‌شده کالای فروش رفته', 'expense', 'debit', '51', 3, true],
            ['52', 'هزینه‌های فروش، توزیع و اداری', 'expense', 'debit', '5', 2, true],
            ['5201', 'حقوق و دستمزد', 'expense', 'debit', '52', 3, true],
            ['5202', 'اجاره', 'expense', 'debit', '52', 3, true],
            ['5203', 'آب، برق، گاز و تلفن', 'expense', 'debit', '52', 3, true],
            ['5204', 'لوازم مصرفی', 'expense', 'debit', '52', 3, true],
            ['5205', 'هزینه تبلیغات و بازاریابی', 'expense', 'debit', '52', 3, true],
            ['5206', 'هزینه حمل و نقل', 'expense', 'debit', '52', 3, true],
            ['5207', 'هزینه‌های بانکی', 'expense', 'debit', '52', 3, true],
            ['5208', 'استهلاک', 'expense', 'debit', '52', 3, true],
            ['5209', 'هزینه‌های متفرقه', 'expense', 'debit', '52', 3, true],
            ['53', 'هزینه‌های مالی', 'expense', 'debit', '5', 2, true],
            ['5301', 'سود و کارمزد بانکی', 'expense', 'debit', '53', 3, true],
            ['54', 'مالیات بر درآمد', 'expense', 'debit', '5', 2, true],
        ];

        $codeToIdMap = [];

        foreach ($accounts as $account) {
            [$code, $name, $type, $nature, $parentCode, $level, $isSystem] = $account;

            $parentId = $parentCode ? ($codeToIdMap[$parentCode] ?? null) : null;

            $created = Account::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'tenant_id' => $tenantId,
                    'parent_id' => $parentId,
                    'code' => $code,
                    'name' => $name,
                    'type' => $type,
                    'nature' => $nature,
                    'level' => $level,
                    'is_active' => true,
                    'is_system' => $isSystem,
                ]
            );

            $codeToIdMap[$code] = $created->id;
        }
    }
}
