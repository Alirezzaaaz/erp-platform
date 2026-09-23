<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\StockChanged;
use App\Events\ThemeUpdated;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BroadcastController extends BaseApiController
{
    public function channels(): JsonResponse
    {
        $tenantId = $this->getTenantId();

        return $this->successResponse([
            'channels' => [
                "tenant.{$tenantId}.inventory",
                "tenant.{$tenantId}.invoices",
                "tenant.{$tenantId}.theme",
            ],
            'events' => [
                'stock.changed',
                'invoice.created',
                'invoice.confirmed',
                'theme.updated',
            ],
            'driver' => config('broadcasting.default'),
        ], 'اطلاعات کانال‌ها دریافت شد.');
    }

    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'in:stock_changed,theme_updated'],
            'payload' => ['nullable', 'array'],
        ]);

        $tenantId = $this->getTenantId();
        $payload = $validated['payload'] ?? [];

        switch ($validated['event']) {
            case 'stock_changed':
                event(new StockChanged(
                    tenantId: $tenantId,
                    productId: (int) ($payload['product_id'] ?? 1),
                    warehouseId: (int) ($payload['warehouse_id'] ?? 1),
                    newQuantity: (float) ($payload['new_quantity'] ?? 100),
                    action: $payload['action'] ?? 'update'
                ));
                break;

            case 'theme_updated':
                $theme = app(ThemeService::class)->getTheme($tenantId);
                event(new ThemeUpdated($tenantId, $theme));
                break;
        }

        return $this->successResponse([
            'event' => $validated['event'],
            'tenant_id' => $tenantId,
            'driver' => config('broadcasting.default'),
        ], 'رویداد ارسال شد.');
    }
}
