<?php

namespace App\Observers;

use App\Services\SyncService;
use Illuminate\Database\Eloquent\Model;

class SyncObserver
{
    public function __construct(protected SyncService $syncService) {}

    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated', array_keys($model->getChanges()));
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    protected function record(Model $model, string $action, ?array $changedFields = null): void
    {
        if (!isset($model->tenant_id) || !$model->tenant_id) {
            return;
        }

        // در زمان seed یا migrate نگیریم
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        try {
            $entityType = $this->getEntityType($model);
            $payload = $action !== 'deleted' ? $model->toArray() : null;

            $this->syncService->recordChange(
                tenantId: (int) $model->tenant_id,
                entityType: $entityType,
                entityId: (int) $model->getKey(),
                action: $action,
                payload: $payload,
                changedFields: $changedFields,
                userId: auth('api')->id(),
                platform: auth('api')->payload()?->get('platform')
            );
        } catch (\Exception $e) {
            \Log::warning('SyncObserver failed', [
                'model' => get_class($model),
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getEntityType(Model $model): string
    {
        $class = class_basename($model);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));
    }
}
