<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureGates();
        $this->configureEvents();
    }

    protected function configureEvents(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ThemeUpdated::class,
            \App\Listeners\ClearThemeCache::class
        );
    }

    /**
     * تعریف Rate Limiterها
     */
    protected function configureRateLimiting(): void
    {
        // Rate Limiter پیش‌فرض API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Rate Limiter برای login (سختگیرانه‌تر)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate Limiter برای register
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
    }

    /**
     * تعریف Gateهای عمومی (اختیاری)
     */
    protected function configureGates(): void
    {
        // Gate ادمین
        Gate::define('admin', function ($user) {
            return $user->hasRole('admin');
        });

        // Gate دسترسی کامل
        Gate::define('manage-all', function ($user) {
            return $user->hasPermission('*');
        });
    }
}
