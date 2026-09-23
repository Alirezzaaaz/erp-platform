<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\WarehouseLayout;
use App\Models\WarehouseLocation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WarehouseLayoutService
{
    /**
     * ایجاد نقشه جدید (Draft)
     */
    public function createLayout(
        int $tenantId,
        int $warehouseId,
        array $data,
        ?int $userId = null
    ): WarehouseLayout {
        $warehouse = Warehouse::where('tenant_id', $tenantId)->findOrFail($warehouseId);

        return DB::transaction(function () use ($tenantId, $warehouseId, $data, $userId) {
            // محاسبه نسخه بعدی
            $nextVersion = (WarehouseLayout::where('warehouse_id', $warehouseId)
                ->max('version') ?? 0) + 1;

            $layout = WarehouseLayout::create([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'version' => $nextVersion,
                'status' => 'draft',
                'layout_data' => [
                    'grid_size' => $data['grid_size'] ?? 10,
                    'background_color' => $data['background_color'] ?? '#FFFFFF',
                ],
                'total_width' => $data['total_width'] ?? 100,
                'total_height' => $data['total_height'] ?? 100,
                'unit_of_measure' => $data['unit_of_measure'] ?? 'meter',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId ?? auth('api')->id(),
            ]);

            return $layout;
        });
    }

    /**
     * به‌روزرسانی متادیتای نقشه
     */
    public function updateLayout(WarehouseLayout $layout, array $data): WarehouseLayout
    {
        if ($layout->status === 'published') {
            throw new InvalidArgumentException('نقشه منتشر شده قابل ویرایش نیست. یک نسخه جدید بسازید.');
        }

        $layout->update([
            'total_width' => $data['total_width'] ?? $layout->total_width,
            'total_height' => $data['total_height'] ?? $layout->total_height,
            'unit_of_measure' => $data['unit_of_measure'] ?? $layout->unit_of_measure,
            'notes' => $data['notes'] ?? $layout->notes,
            'layout_data' => array_merge($layout->layout_data ?? [], $data['layout_data'] ?? []),
        ]);

        return $layout->fresh();
    }

    /**
     * افزودن موقعیت (Zone/Aisle/Rack/Shelf/Bin)
     */
    public function addLocation(WarehouseLayout $layout, array $data): WarehouseLocation
    {
        if ($layout->status === 'published') {
            throw new InvalidArgumentException('نقشه منتشر شده قابل ویرایش نیست.');
        }

        // اعتبارسنجی
        $this->validateLocation($layout, $data);

        return WarehouseLocation::create([
            'tenant_id' => $layout->tenant_id,
            'warehouse_layout_id' => $layout->id,
            'code' => $data['code'],
            'type' => $data['type'],
            'name' => $data['name'],
            'pos_x' => $data['pos_x'] ?? 0,
            'pos_y' => $data['pos_y'] ?? 0,
            'pos_z' => $data['pos_z'] ?? 0,
            'width' => $data['width'] ?? 1,
            'depth' => $data['depth'] ?? 1,
            'height' => $data['height'] ?? 1,
            'capacity' => $data['capacity'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * افزودن انبوه موقعیت‌ها (Import)
     */
    public function bulkAddLocations(WarehouseLayout $layout, array $locations): array
    {
        if ($layout->status === 'published') {
            throw new InvalidArgumentException('نقشه منتشر شده قابل ویرایش نیست.');
        }

        $created = [];
        $errors = [];

        return DB::transaction(function () use ($layout, $locations, &$created, &$errors) {
            foreach ($locations as $index => $data) {
                try {
                    $this->validateLocation($layout, $data);
                    $created[] = $this->addLocation($layout, $data);
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'code' => $data['code'] ?? '?',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'created' => $created,
                'errors' => $errors,
                'created_count' => count($created),
                'error_count' => count($errors),
            ];
        });
    }

    /**
     * به‌روزرسانی موقعیت
     */
    public function updateLocation(WarehouseLocation $location, array $data): WarehouseLocation
    {
        $layout = $location->layout;
        if ($layout->status === 'published') {
            throw new InvalidArgumentException('نقشه منتشر شده قابل ویرایش نیست.');
        }

        // بررسی تداخل اگر موقعیت تغییر کرده
        if (isset($data['pos_x']) || isset($data['pos_y']) || isset($data['width']) || isset($data['depth'])) {
            $this->validateLocation($layout, array_merge($location->toArray(), $data), $location->id);
        }

        $location->update($data);

        return $location->fresh();
    }

    /**
     * حذف موقعیت
     */
    public function deleteLocation(WarehouseLocation $location): void
    {
        $layout = $location->layout;
        if ($layout->status === 'published') {
            throw new InvalidArgumentException('نقشه منتشر شده قابل ویرایش نیست.');
        }

        $location->delete();
    }

    /**
     * انتشار نقشه (Draft → Published)
     */
    public function publishLayout(WarehouseLayout $layout, ?int $userId = null): WarehouseLayout
    {
        if ($layout->status === 'published') {
            throw new InvalidArgumentException('نقشه قبلاً منتشر شده است.');
        }

        if ($layout->status === 'archived') {
            throw new InvalidArgumentException('نقشه آرشیو شده قابل انتشار نیست.');
        }

        return DB::transaction(function () use ($layout, $userId) {
            // اعتبارسنجی نهایی
            $this->validateLayoutForPublish($layout);

            // آرشیو کردن نسخه‌های منتشر شده قبلی
            WarehouseLayout::where('warehouse_id', $layout->warehouse_id)
                ->where('status', 'published')
                ->update(['status' => 'archived']);

            // انتشار
            $layout->update([
                'status' => 'published',
                'published_by' => $userId ?? auth('api')->id(),
                'published_at' => now(),
            ]);

            return $layout->fresh(['locations']);
        });
    }

    /**
     * ایجاد نسخه جدید از نقشه منتشر شده (برای ویرایش)
     */
    public function cloneLayout(WarehouseLayout $layout, ?int $userId = null): WarehouseLayout
    {
        return DB::transaction(function () use ($layout, $userId) {
            $layout->load('locations');

            $nextVersion = (WarehouseLayout::where('warehouse_id', $layout->warehouse_id)
                ->max('version') ?? 0) + 1;

            $newLayout = WarehouseLayout::create([
                'tenant_id' => $layout->tenant_id,
                'warehouse_id' => $layout->warehouse_id,
                'version' => $nextVersion,
                'status' => 'draft',
                'layout_data' => $layout->layout_data,
                'total_width' => $layout->total_width,
                'total_height' => $layout->total_height,
                'unit_of_measure' => $layout->unit_of_measure,
                'notes' => "کپی از نسخه {$layout->version}",
                'created_by' => $userId ?? auth('api')->id(),
            ]);

            // کپی موقعیت‌ها
            foreach ($layout->locations as $loc) {
                WarehouseLocation::create([
                    'tenant_id' => $loc->tenant_id,
                    'warehouse_layout_id' => $newLayout->id,
                    'code' => $loc->code,
                    'type' => $loc->type,
                    'name' => $loc->name,
                    'pos_x' => $loc->pos_x,
                    'pos_y' => $loc->pos_y,
                    'pos_z' => $loc->pos_z,
                    'width' => $loc->width,
                    'depth' => $loc->depth,
                    'height' => $loc->height,
                    'capacity' => $loc->capacity,
                    'metadata' => $loc->metadata,
                ]);
            }

            return $newLayout->fresh(['locations']);
        });
    }

    /**
     * اعتبارسنجی یک موقعیت
     */
    protected function validateLocation(WarehouseLayout $layout, array $data, ?int $excludeId = null): void
    {
        // ۱. بررسی نوع
        $validTypes = ['zone', 'aisle', 'rack', 'shelf', 'bin'];
        if (!in_array($data['type'] ?? '', $validTypes, true)) {
            throw new InvalidArgumentException('نوع موقعیت نامعتبر است. مقادیر مجاز: ' . implode(', ', $validTypes));
        }

        // ۲. کد یکتا در همان نقشه
        $exists = WarehouseLocation::where('warehouse_layout_id', $layout->id)
            ->where('code', $data['code'] ?? '')
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException("کد '{$data['code']}' قبلاً در این نقشه استفاده شده است.");
        }

        // ۳. بررسی تداخل (فقط برای قفسه‌ها و طبقات)
        if (in_array($data['type'] ?? '', ['rack', 'shelf'], true)) {
            $this->checkOverlap($layout, $data, $excludeId);
        }

        // ۴. بررسی موقعیت در محدوده نقشه
        if (isset($data['pos_x']) && isset($data['width'])) {
            $endX = $data['pos_x'] + $data['width'];
            if ($endX > $layout->total_width) {
                throw new InvalidArgumentException("موقعیت در محور X از مرز نقشه ({$layout->total_width}) فراتر می‌رود.");
            }
        }
        if (isset($data['pos_y']) && isset($data['depth'])) {
            $endY = $data['pos_y'] + $data['depth'];
            if ($endY > $layout->total_height) {
                throw new InvalidArgumentException("موقعیت در محور Y از مرز نقشه ({$layout->total_height}) فراتر می‌رود.");
            }
        }
    }

    /**
     * بررسی تداخل با قفسه‌های دیگر
     */
    protected function checkOverlap(WarehouseLayout $layout, array $data, ?int $excludeId = null): void
    {
        $x1 = (float) ($data['pos_x'] ?? 0);
        $y1 = (float) ($data['pos_y'] ?? 0);
        $x2 = $x1 + (float) ($data['width'] ?? 0);
        $y2 = $y1 + (float) ($data['depth'] ?? 0);

        $existing = WarehouseLocation::where('warehouse_layout_id', $layout->id)
            ->whereIn('type', ['rack', 'shelf'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get();

        foreach ($existing as $loc) {
            $lx1 = (float) $loc->pos_x;
            $ly1 = (float) $loc->pos_y;
            $lx2 = $lx1 + (float) $loc->width;
            $ly2 = $ly1 + (float) $loc->depth;

            // بررسی تداخل دو مستطیل
            $overlap = !($x2 <= $lx1 || $x1 >= $lx2 || $y2 <= $ly1 || $y1 >= $ly2);

            if ($overlap) {
                throw new InvalidArgumentException("موقعیت جدید با '{$loc->code}' تداخل دارد.");
            }
        }
    }

    /**
     * اعتبارسنجی نهایی برای انتشار
     */
    protected function validateLayoutForPublish(WarehouseLayout $layout): void
    {
        $locationsCount = $layout->locations()->count();

        if ($locationsCount === 0) {
            throw new InvalidArgumentException('نقشه بدون موقعیت قابل انتشار نیست. حداقل یک قفسه اضافه کنید.');
        }
    }

    /**
     * آمار نقشه
     */
    public function getLayoutStats(WarehouseLayout $layout): array
    {
        $locations = $layout->locations;

        $byType = $locations->groupBy('type')->map->count();

        return [
            'total_locations' => $locations->count(),
            'by_type' => $byType,
            'total_capacity' => (int) $locations->sum('capacity'),
            'total_area' => round($locations->sum(fn ($l) => (float) $l->width * (float) $l->depth), 2),
        ];
    }
}
