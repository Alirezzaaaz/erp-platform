<?php

namespace App\Listeners;

use App\Events\ThemeUpdated;
use App\Services\ThemeService;

class ClearThemeCache
{
    public function __construct(protected ThemeService $themeService) {}

    public function handle(ThemeUpdated $event): void
    {
        $this->themeService->clearCache($event->tenantId);
    }
}
