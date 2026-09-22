<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ThemeResource;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeController extends BaseApiController
{
    public function __construct(protected ThemeService $themeService) {}

    /**
     * دریافت تم مستاجر جاری
     */
    public function show(): JsonResponse
    {
        $theme = $this->themeService->getTheme($this->getTenantId());

        return $this->successResponse(
            new ThemeResource($theme),
            'تم با موفقیت دریافت شد.'
        );
    }

    /**
     * به‌روزرسانی تم
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primaryColor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondaryColor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'successColor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'warningColor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'errorColor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'backgroundColor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'fontFamily' => ['nullable', 'string', 'in:Vazirmatn,IRANSans,Shabnam,Sahel,YekanBakh'],
            'borderRadius' => ['nullable', 'integer', 'min:0', 'max:24'],
            'logoUrl' => ['nullable', 'url', 'max:500'],
            'faviconUrl' => ['nullable', 'url', 'max:500'],
            'themeMode' => ['nullable', 'in:light,dark,auto'],
            'layoutConfig' => ['nullable', 'array'],
            'customTokens' => ['nullable', 'array'],
        ], [
            'primaryColor.regex' => 'رنگ اصلی باید فرمت hex معتبر داشته باشد (مثال: #1976D2).',
            'fontFamily.in' => 'فونت انتخاب‌شده پشتیبانی نمی‌شود.',
            'borderRadius.max' => 'شعاع گوشه‌ها نمی‌تواند بیشتر از ۲۴ پیکسل باشد.',
        ]);

        $theme = $this->themeService->updateTheme($this->getTenantId(), $validated);

        return $this->successResponse(
            new ThemeResource($theme),
            'تم با موفقیت به‌روزرسانی شد.'
        );
    }

    /**
     * بازنشانی به تم پیش‌فرض
     */
    public function reset(): JsonResponse
    {
        $theme = $this->themeService->resetTheme($this->getTenantId());

        return $this->successResponse(
            new ThemeResource($theme),
            'تم به حالت پیش‌فرض بازگشت.'
        );
    }

    /**
     * نسخه سبک (فقط رنگ‌ها برای کش کلاینت)
     */
    public function version(): JsonResponse
    {
        $theme = $this->themeService->getTheme($this->getTenantId());

        return response()->json([
            'version' => $theme->version,
            'updated_at' => $theme->updated_at?->toIso8601String(),
        ]);
    }
}
