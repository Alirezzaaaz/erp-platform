<?php

namespace App\Services;

use App\Models\SyncChange;
use App\Models\SyncClient;
use Illuminate\Support\Facades\DB;

class SyncService
{
    /**
     * ثبت تغییر در sync_changes
     */
    public function recordChange(
        int $tenantId,
        string $entityType,
        int $entityId,
        string $action,
        ?array $payload = null,
        ?array $changedFields = null,
        ?int $userId = null,
        ?string $platform = null
    ): SyncChange {
        return SyncChange::create([
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'payload' => $payload,
            'changed_fields' => $changedFields,
            'user_id' => $userId,
            'source_platform' => $platform,
        ]);
    }

    /**
     * Pull: دریافت تغییرات جدید از سرور برای کلاینت
     *
     * @param  array|null  $entityTypes  اگر null، همه entityها
     */
    public function pull(
        int $tenantId,
        string $clientId,
        int $sinceChangeId = 0,
        ?array $entityTypes = null,
        int $limit = 500
    ): array {
        $client = $this->getOrCreateClient($tenantId, $clientId);

        // اگر since=0، از آخرین sync کلاینت استفاده کن
        if ($sinceChangeId === 0) {
            $sinceChangeId = $client->last_sync_change_id;
        }

        $query = SyncChange::where('tenant_id', $tenantId)
            ->where('id', '>', $sinceChangeId)
            ->orderBy('id');

        if ($entityTypes) {
            $query->whereIn('entity_type', $entityTypes);
        }

        $changes = $query->limit($limit)->get();

        $lastChangeId = $changes->isNotEmpty()
            ? $changes->last()->id
            : $sinceChangeId;

        // تعداد کل تغییرات باقی‌مانده
        $remainingQuery = SyncChange::where('tenant_id', $tenantId)
            ->where('id', '>', $lastChangeId);

        if ($entityTypes) {
            $remainingQuery->whereIn('entity_type', $entityTypes);
        }

        $hasMore = $remainingQuery->count() > 0;

        // به‌روزرسانی کلاینت
        $client->update([
            'last_sync_change_id' => $lastChangeId,
            'last_sync_at' => now(),
        ]);

        return [
            'changes' => $changes->map(fn ($c) => [
                'id' => $c->id,
                'entity_type' => $c->entity_type,
                'entity_id' => $c->entity_id,
                'action' => $c->action,
                'payload' => $c->payload,
                'changed_fields' => $c->changed_fields,
                'source_platform' => $c->source_platform,
                'created_at' => $c->created_at?->toIso8601String(),
            ])->toArray(),
            'last_change_id' => $lastChangeId,
            'has_more' => $hasMore,
            'total_returned' => $changes->count(),
        ];
    }

    /**
     * Push: دریافت تغییرات از کلاینت و اعمال در سرور
     */
    public function push(
        int $tenantId,
        string $clientId,
        array $changes,
        ?string $platform = null
    ): array {
        $client = $this->getOrCreateClient($tenantId, $clientId);

        $applied = [];
        $conflicts = [];
        $errors = [];

        foreach ($changes as $change) {
            try {
                $result = $this->applyChange($tenantId, $change, $platform);

                if ($result['status'] === 'applied') {
                    $applied[] = $result;
                } elseif ($result['status'] === 'conflict') {
                    $conflicts[] = $result;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'change' => $change,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $client->update(['last_sync_at' => now()]);

        return [
            'applied' => $applied,
            'conflicts' => $conflicts,
            'errors' => $errors,
            'applied_count' => count($applied),
            'conflict_count' => count($conflicts),
            'error_count' => count($errors),
        ];
    }

    /**
     * اعمال یک تغییر از کلاینت
     */
    protected function applyChange(int $tenantId, array $change, ?string $platform): array
    {
        $entityType = $change['entity_type'] ?? null;
        $entityId = $change['entity_id'] ?? null;
        $action = $change['action'] ?? null;
        $clientTimestamp = $change['client_timestamp'] ?? null;

        if (!$entityType || !$action) {
            throw new \InvalidArgumentException('entity_type و action الزامی است.');
        }

        // بررسی تعارض: اگر نسخه سرور جدیدتر از کلاینت باشد
        if ($entityId && in_array($action, ['updated', 'deleted'])) {
            $lastChange = SyncChange::where('tenant_id', $tenantId)
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->orderByDesc('id')
                ->first();

            if ($lastChange && $clientTimestamp) {
                $clientTime = \Carbon\Carbon::parse($clientTimestamp);
                if ($lastChange->created_at->gt($clientTime)) {
                    return [
                        'status' => 'conflict',
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'server_change' => [
                            'action' => $lastChange->action,
                            'updated_at' => $lastChange->created_at->toIso8601String(),
                        ],
                    ];
                }
            }
        }

        // Apply the change (در اینجا فقط log می‌کنیم - پیاده‌سازی کامل در sprint بعد)
        // در واقع، برای هر entity باید کنترلر مربوطه فراخوانی شود.

        $syncChange = $this->recordChange(
            tenantId: $tenantId,
            entityType: $entityType,
            entityId: $entityId ?? 0,
            action: $action,
            payload: $change['payload'] ?? null,
            changedFields: $change['changed_fields'] ?? null,
            platform: $platform
        );

        return [
            'status' => 'applied',
            'change_id' => $syncChange->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ];
    }

    /**
     * دریافت یا ایجاد کلاینت
     */
    protected function getOrCreateClient(int $tenantId, string $clientId): SyncClient
    {
        return SyncClient::firstOrCreate(
            ['client_id' => $clientId],
            [
                'tenant_id' => $tenantId,
                'client_id' => $clientId,
                'platform' => 'unknown',
                'last_sync_change_id' => 0,
            ]
        );
    }

    /**
     * ثبت کلاینت جدید
     */
    public function registerClient(
        int $tenantId,
        string $clientId,
        string $platform,
        ?string $deviceName = null,
        ?string $appVersion = null
    ): SyncClient {
        return SyncClient::updateOrCreate(
            ['client_id' => $clientId],
            [
                'tenant_id' => $tenantId,
                'platform' => $platform,
                'device_name' => $deviceName,
                'app_version' => $appVersion,
                'is_active' => true,
            ]
        );
    }

    /**
     * وضعیت sync برای کلاینت
     */
    public function status(int $tenantId, string $clientId): array
    {
        $client = SyncClient::where('client_id', $clientId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$client) {
            return [
                'registered' => false,
                'latest_change_id' => SyncChange::where('tenant_id', $tenantId)->max('id') ?? 0,
                'total_changes' => SyncChange::where('tenant_id', $tenantId)->count(),
            ];
        }

        $latestChangeId = SyncChange::where('tenant_id', $tenantId)->max('id') ?? 0;

        return [
            'registered' => true,
            'client_id' => $client->client_id,
            'platform' => $client->platform,
            'last_sync_change_id' => $client->last_sync_change_id,
            'last_sync_at' => $client->last_sync_at?->toIso8601String(),
            'latest_change_id' => $latestChangeId,
            'pending_changes' => max(0, $latestChangeId - $client->last_sync_change_id),
            'total_changes' => SyncChange::where('tenant_id', $tenantId)->count(),
        ];
    }
}
