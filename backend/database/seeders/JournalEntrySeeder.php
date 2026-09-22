<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\AccountingService;
use Illuminate\Database\Seeder;

class JournalEntrySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('subdomain', 'demo')->first();
        if (!$tenant) return;

        $service = app(AccountingService::class);
        $admin = $tenant->users()->first();

        // سرمایه اولیه
        try {
            $service->createJournalEntry(
                tenantId: $tenant->id,
                lines: [
                    ['account_code' => '110102', 'debit' => 500000000, 'credit' => 0, 'description' => 'واریز به بانک'],
                    ['account_code' => '3101', 'debit' => 0, 'credit' => 500000000, 'description' => 'سرمایه اولیه'],
                ],
                description: 'ثبت سرمایه اولیه شرکت',
                userId: $admin?->id,
                autoApprove: true
            );

            // خرید اثاثه
            $service->createJournalEntry(
                tenantId: $tenant->id,
                lines: [
                    ['account_code' => '120104', 'debit' => 80000000, 'credit' => 0, 'description' => 'خرید اثاثه'],
                    ['account_code' => '110102', 'debit' => 0, 'credit' => 80000000, 'description' => 'پرداخت از بانک'],
                ],
                description: 'خرید اثاثه اداری',
                userId: $admin?->id,
                autoApprove: true
            );

            // هزینه اجاره
            $service->createJournalEntry(
                tenantId: $tenant->id,
                lines: [
                    ['account_code' => '5202', 'debit' => 20000000, 'credit' => 0, 'description' => 'هزینه اجاره'],
                    ['account_code' => '110102', 'debit' => 0, 'credit' => 20000000, 'description' => 'پرداخت از بانک'],
                ],
                description: 'پرداخت اجاره ماه',
                userId: $admin?->id,
                autoApprove: true
            );

            $this->command->info('✅ ۳ سند نمونه ثبت شد.');
        } catch (\Exception $e) {
            $this->command->error('خطا در ثبت سند: ' . $e->getMessage());
        }
    }
}
