<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'احراز هویت نشده‌اید.',
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        if (!$user->tenant || !$user->tenant->is_active) {
            return response()->json([
                'message' => 'کسب‌وکار شما غیرفعال است.',
                'code' => 'TENANT_INACTIVE',
            ], 403);
        }

        // بررسی پایان دوره آزمایشی
        $tenant = $user->tenant;
        if ($tenant->trial_ends_at && $tenant->trial_ends_at->isPast() && !$tenant->is_active) {
            return response()->json([
                'message' => 'دوره آزمایشی به پایان رسیده است.',
                'code' => 'TRIAL_EXPIRED',
            ], 403);
        }

        return $next($request);
    }
}
