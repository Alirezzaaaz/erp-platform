<?php

namespace Database\Seeders;

use App\Models\Party;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PartySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('subdomain', 'demo')->first();
        if (!$tenant) return;

        $parties = [
            ['type' => 'customer', 'code' => 'C-001', 'name' => 'شرکت بازرگانی پارس', 'company_name' => 'پارس تجارت', 'mobile' => '09121111111', 'email' => 'info@pars.ir', 'credit_limit' => 50000000],
            ['type' => 'customer', 'code' => 'C-002', 'name' => 'فروشگاه زنجیره‌ای افق کوروش', 'company_name' => 'افق کوروش', 'mobile' => '09122222222', 'credit_limit' => 100000000],
            ['type' => 'supplier', 'code' => 'S-001', 'name' => 'شرکت پخش سراسری', 'company_name' => 'پخش سراسری ایران', 'mobile' => '09123333333', 'email' => 'sales@dist.ir'],
            ['type' => 'both', 'code' => 'B-001', 'name' => 'بازرگانی دوطرفه', 'company_name' => 'بازرگانی متقابل', 'mobile' => '09124444444'],
        ];

        foreach ($parties as $party) {
            Party::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $party['code']],
                array_merge($party, ['tenant_id' => $tenant->id, 'is_active' => true])
            );
        }
    }
}
