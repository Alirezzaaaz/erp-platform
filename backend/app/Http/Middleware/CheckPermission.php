<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * بررسی مجوز کاربر
     * مثال: middleware('permission:invoices.create')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'احراز هویت نشده‌اید.',
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'message' => 'شما مجوز لازم برای این عملیات را ندارید.',
                'code' => 'PERMISSION_DENIED',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
