<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Party;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\InvoiceService;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('subdomain', 'demo')->first();
        if (!$tenant) return;

        // بررسی اینکه آیا فاکتور از قبل هست
        if (Invoice::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $warehouse = Warehouse::where('tenant_id', $tenant->id)->where('is_default', true)->first();
        $customer = Party::where('tenant_id', $tenant->id)->where('type', 'customer')->first();
        $supplier = Party::where('tenant_id', $tenant->id)->where('type', 'supplier')->first();
        $products = Product::where('tenant_id', $tenant->id)->take(3)->get();

        if (!$warehouse || !$customer || $products->isEmpty()) {
            $this->command->warn('داده‌های کافی برای ساخت فاکتور نیست.');
            return;
        }

        $service = app(InvoiceService::class);

        // فاکتور فروش نمونه
        try {
            $invoice = $service->createInvoice(
                tenantId: $tenant->id,
                type: 'sale',
                partyId: $customer->id,
                items: $products->map(fn ($p) => [
                    'product_id' => $p->id,
                    'quantity' => rand(2, 5),
                    'unit_price' => $p->sale_price,
                    'tax_percent' => 9,
                ])->toArray(),
                warehouseId: $warehouse->id,
                notes: 'فاکتور فروش نمونه',
                userId: $tenant->users()->first()?->id
            );

            $service->confirmInvoice($invoice, $tenant->users()->first()?->id);

            $this->command->info("✅ فاکتور فروش {$invoice->number} صادر و تایید شد.");

            // فاکتور خرید نمونه
            if ($supplier && $products->count() >= 2) {
                $purchaseInvoice = $service->createInvoice(
                    tenantId: $tenant->id,
                    type: 'purchase',
                    partyId: $supplier->id,
                    items: [
                        ['product_id' => $products[0]->id, 'quantity' => 10, 'unit_price' => 25000000, 'tax_percent' => 9],
                        ['product_id' => $products[1]->id, 'quantity' => 20, 'unit_price' => 800000, 'tax_percent' => 9],
                    ],
                    warehouseId: $warehouse->id,
                    notes: 'فاکتور خرید نمونه',
                    userId: $tenant->users()->first()?->id
                );

                $service->confirmInvoice($purchaseInvoice, $tenant->users()->first()?->id);

                $this->command->info("✅ فاکتور خرید {$purchaseInvoice->number} صادر و تایید شد.");
            }
        } catch (\Exception $e) {
            $this->command->error('خطا در ساخت فاکتور: ' . $e->getMessage());
        }
    }
}
