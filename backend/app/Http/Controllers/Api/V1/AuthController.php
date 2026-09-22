<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * ورود کاربر
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->findBySubdomain($request->subdomain);

        if (!$tenant) {
            throw ValidationException::withMessages([
                'subdomain' => ['کسب‌وکار مورد نظر یافت نشد یا غیرفعال است.'],
            ]);
        }

        if ($tenant->trial_ends_at && $tenant->trial_ends_at->isPast() && !$tenant->is_active) {
            return response()->json([
                'message' => 'دوره آزمایشی به پایان رسیده است.',
            ], 403);
        }

        $user = User::where('tenant_id', $tenant->id)
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['ایمیل یا رمز عبور نامعتبر است.'],
            ]);
        }

        if (!$user->isActive()) {
            return response()->json([
                'message' => 'حساب کاربری شما غیرفعال است. با پشتیبانی تماس بگیرید.',
            ], 403);
        }

        // ساخت توکن با اطلاعات اضافی
        $token = Auth::guard('api')->claims([
            'tenant_id' => $tenant->id,
            'platform' => $request->platform,
        ])->login($user);

        // ثبت اطلاعات آخرین ورود
        $user->update([
            'last_login_at' => now(),
            'last_login_platform' => $request->platform,
        ]);

        return $this->respondWithToken($token, $user, $tenant);
    }

    /**
     * ثبت‌نام مستاجر جدید
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->createTenant($request->validated());

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => $request->password,
            'status' => 'active',
        ]);

        // اختصاص نقش admin
        $adminRole = $tenant->roles()->where('name', 'admin')->first();
        if ($adminRole) {
            $user->roles()->attach($adminRole->id);
        }

        $token = Auth::guard('api')->claims([
            'tenant_id' => $tenant->id,
            'platform' => $request->platform,
        ])->login($user);

        $user->update([
            'last_login_at' => now(),
            'last_login_platform' => $request->platform,
        ]);

        return $this->respondWithToken($token, $user, $tenant, 201);
    }

    /**
     * اطلاعات کاربر جاری
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        $user->load('tenant', 'roles');

        return response()->json([
            'user' => $this->formatUser($user),
            'tenant' => [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name,
                'subdomain' => $user->tenant->subdomain,
                'business_type' => $user->tenant->business_type,
                'size_category' => $user->tenant->size_category,
            ],
        ]);
    }

    /**
     * خروج
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json([
            'message' => 'با موفقیت خارج شدید.',
        ]);
    }

    /**
     * تازه‌سازی توکن
     */
    public function refresh(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        $tenant = $user->tenant;
        $token = Auth::guard('api')->refresh();

        return $this->respondWithToken($token, $user, $tenant);
    }

    /**
     * فرمت خروجی توکن
     */
    protected function respondWithToken(string $token, User $user, $tenant, int $status = 200): JsonResponse
    {
        $user->load('roles');

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $this->formatUser($user),
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
            ],
        ], $status);
    }

    /**
     * فرمت کاربر
     */
    protected function formatUser(User $user): array
    {
        $role = $user->roles->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'avatar_url' => $user->avatar_url,
            'status' => $user->status,
            'role' => $role ? [
                'name' => $role->name,
                'display_name' => $role->display_name,
                'permissions' => $role->permissions ?? [],
            ] : null,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ];
    }
}
