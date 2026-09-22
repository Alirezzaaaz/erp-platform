<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('subdomain', 'demo')->first();
        if (!$tenant) {
            return;
        }

        $this->createSampleProducts($tenant->id);
    }

    protected function createSampleProducts(int $tenantId): void
    {
        $units = Unit::where('tenant_id', $tenantId)->pluck('id', 'name');
        $numberUnit = $units['عدد'] ?? $units->first();
        $kgUnit = $units['کیلوگرم'] ?? $units->first();

        // ساخت دسته‌بندی‌ها
        $electronicsCat = ProductCategory::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'لوازم الکترونیکی'],
            ['tenant_id' => $tenantId, 'name' => 'لوازم الکترونیکی', 'code' => 'ELEC', 'is_active' => true]
        );

        $foodCat = ProductCategory::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'مواد غذایی'],
            ['tenant_id' => $tenantId, 'name' => 'مواد غذایی', 'code' => 'FOOD', 'is_active' => true]
        );

        $officeCat = ProductCategory::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'لوازم اداری'],
            ['tenant_id' => $tenantId, 'name' => 'لوازم اداری', 'code' => 'OFFC', 'is_active' => true]
        );

        $products = [
            ['code' => 'ELEC-001', 'barcode' => '6260000001', 'name' => 'لپ‌تاپ ایسوس VivoBook', 'category_id' => $electronicsCat->id, 'unit_id' => $numberUnit, 'purchase_price' => 25000000, 'sale_price' => 28500000, 'min_stock' => 3, 'reorder_point' => 5],
            ['code' => 'ELEC-002', 'barcode' => '6260000002', 'name' => 'ماوس لاجیتک M331', 'category_id' => $electronicsCat->id, 'unit_id' => $numberUnit, 'purchase_price' => 850000, 'sale_price' => 1100000, 'min_stock' => 10, 'reorder_point' => 15],
            ['code' => 'ELEC-003', 'barcode' => '6260000003', 'name' => 'کیبورد مکانیکال ردراگون', 'category_id' => $electronicsCat->id, 'unit_id' => $numberUnit, 'purchase_price' => 1200000, 'sale_price' => 1650000, 'min_stock' => 5, 'reorder_point' => 8],
            ['code' => 'FOOD-001', 'barcode' => '6261000001', 'name' => 'برنج ایرانی هاشمی', 'category_id' => $foodCat->id, 'unit_id' => $kgUnit, 'purchase_price' => 85000, 'sale_price' => 120000, 'min_stock' => 50, 'reorder_point' => 100],
            ['code' => 'FOOD-002', 'barcode' => '6261000002', 'name' => 'روغن آفتابگردان ۱.۵ لیتری', 'category_id' => $foodCat->id, 'unit_id' => $numberUnit, 'purchase_price' => 95000, 'sale_price' => 125000, 'min_stock' => 30, 'reorder_point' => 50],
            ['code' => 'OFFC-001', 'barcode' => '6262000001', 'name' => 'کاغذ A4 بسته ۵۰۰ برگ', 'category_id' => $officeCat->id, 'unit_id' => $numberUnit, 'purchase_price' => 180000, 'sale_price' => 220000, 'min_stock' => 20, 'reorder_point' => 30],
            ['code' => 'OFFC-002', 'barcode' => '6262000002', 'name' => 'خودکار بیک آبی', 'category_id' => $officeCat->id, 'unit_id' => $numberUnit, 'purchase_price' => 5000, 'sale_price' => 8000, 'min_stock' => 100, 'reorder_point' => 200],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $product['code']],
                array_merge($product, [
                    'tenant_id' => $tenantId,
                    'is_active' => true,
                    'track_stock' => true,
                ])
            );
        }
    }
}
