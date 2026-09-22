<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * بررسی نقش کاربر
     * مثال: middleware('role:admin,accountant')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'احراز هویت نشده‌اید.',
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        $userRole = $user->roles->first()?->name;

        if (!in_array($userRole, $roles, true)) {
            return response()->json([
                'message' => 'شما مجوز لازم برای این عملیات را ندارید.',
                'code' => 'ROLE_NOT_ALLOWED',
                'required_roles' => $roles,
            ], 403);
        }

        return $next($request);
    }
}
