<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends BaseApiController
{
    public function __construct(protected SyncService $syncService) {}

    /**
     * ثبت کلاینت جدید
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string', 'max:100'],
            'platform' => ['required', 'in:windows,android,web'],
            'device_name' => ['nullable', 'string', 'max:200'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        $client = $this->syncService->registerClient(
            tenantId: $this->getTenantId(),
            clientId: $validated['client_id'],
            platform: $validated['platform'],
            deviceName: $validated['device_name'] ?? null,
            appVersion: $validated['app_version'] ?? null
        );

        return $this->successResponse([
            'client_id' => $client->client_id,
            'last_sync_change_id' => $client->last_sync_change_id,
            'registered_at' => $client->created_at?->toIso8601String(),
        ], 'کلاینت با موفقیت ثبت شد.');
    }

    /**
     * Pull: دریافت تغییرات جدید از سرور
     */
    public function pull(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string', 'max:100'],
            'since_change_id' => ['nullable', 'integer', 'min:0'],
            'entity_types' => ['nullable', 'array'],
            'entity_types.*' => ['string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $result = $this->syncService->pull(
            tenantId: $this->getTenantId(),
            clientId: $validated['client_id'],
            sinceChangeId: (int) ($validated['since_change_id'] ?? 0),
            entityTypes: $validated['entity_types'] ?? null,
            limit: (int) ($validated['limit'] ?? 500)
        );

        return $this->successResponse($result, 'تغییرات دریافت شد.');
    }

    /**
     * Push: ارسال تغییرات از کلاینت
     */
    public function push(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string', 'max:100'],
            'changes' => ['required', 'array', 'min:1'],
            'changes.*.entity_type' => ['required', 'string'],
            'changes.*.action' => ['required', 'in:created,updated,deleted'],
            'changes.*.entity_id' => ['nullable', 'integer'],
            'changes.*.payload' => ['nullable', 'array'],
            'changes.*.changed_fields' => ['nullable', 'array'],
            'changes.*.client_timestamp' => ['nullable', 'date'],
        ]);

        $result = $this->syncService->push(
            tenantId: $this->getTenantId(),
            clientId: $validated['client_id'],
            changes: $validated['changes'],
            platform: auth('api')->payload()?->get('platform')
        );

        return $this->successResponse($result, 'تغییرات اعمال شد.');
    }

    /**
     * وضعیت sync
     */
    public function status(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => ['required', 'string', 'max:100'],
        ]);

        $result = $this->syncService->status(
            tenantId: $this->getTenantId(),
            clientId: $request->input('client_id')
        );

        return $this->successResponse($result, 'وضعیت sync دریافت شد.');
    }
}
