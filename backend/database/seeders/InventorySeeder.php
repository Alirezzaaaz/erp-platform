<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('subdomain', 'demo')->first();
        if (!$tenant) return;

        $warehouse = Warehouse::where('tenant_id', $tenant->id)->where('is_default', true)->first();
        if (!$warehouse) return;

        $service = app(InventoryService::class);

        $products = Product::where('tenant_id', $tenant->id)->get();
        $admin = $tenant->users()->first();

        foreach ($products as $product) {
            // موجودی اولیه تصادفی بین 20 تا 200 عدد
            $qty = rand(20, 200);
            $unitCost = (float) $product->purchase_price;

            $service->increaseStock(
                tenantId: $tenant->id,
                warehouseId: $warehouse->id,
                productId: $product->id,
                quantity: $qty,
                unitCost: $unitCost,
                referenceType: 'manual',
                description: 'موجودی اولیه',
                userId: $admin?->id
            );
        }

        $this->command->info('✅ موجودی اولیه برای ' . $products->count() . ' کالا ثبت شد.');
    }
}
