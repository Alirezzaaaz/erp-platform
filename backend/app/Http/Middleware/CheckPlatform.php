<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlatform
{
    /**
     * بررسی می‌کند که درخواست از پلتفرم مجاز آمده باشد.
     * مثال: middleware('platform:windows')
     */
    public function handle(Request $request, Closure $next, string ...$allowedPlatforms): Response
    {
        $payload = auth('api')->payload();
        $currentPlatform = $payload->get('platform');

        if (!in_array($currentPlatform, $allowedPlatforms, true)) {
            return response()->json([
                'message' => 'این عملیات فقط از پلتفرم ' . implode(' یا ', $allowedPlatforms) . ' قابل انجام است.',
                'code' => 'PLATFORM_NOT_ALLOWED',
                'current_platform' => $currentPlatform,
                'allowed_platforms' => $allowedPlatforms,
            ], 403);
        }

        return $next($request);
    }
}
