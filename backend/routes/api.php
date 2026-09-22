<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ---------- مسیرهای عمومی (بدون احراز هویت) ----------
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login'); // ۵ تلاش در دقیقه

    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:register'); // ۳ ثبت‌نام در دقیقه

    // ---------- مسیرهای محافظت‌شده ----------
    Route::middleware(['auth:api', 'tenant'])->group(function () {

        // احراز هویت
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
        });

        // ---------- مسیرهای فقط ویندوز (طراحی نقشه) ----------
        Route::middleware(['platform:windows', 'role:admin'])->prefix('warehouse-map')->group(function () {
            Route::post('/layouts', fn () => response()->json(['message' => 'ثبت نقشه (در Sprint بعدی)']));
            Route::put('/layouts/{id}', fn () => response()->json(['message' => 'ویرایش نقشه (در Sprint بعدی)']));
            Route::post('/layouts/{id}/publish', fn () => response()->json(['message' => 'انتشار نقشه (در Sprint بعدی)']));
        });

        // ---------- مسیرهای فقط مشاهده نقشه (همه پلتفرم‌ها) ----------
        Route::get('/warehouse-map/layouts/{id}', fn () => response()->json(['message' => 'مشاهده نقشه']));

        // ---------- نمونه مسیرهای RBAC ----------
        Route::middleware('permission:accounting.view')->get('/accounting/journal', fn () => response()->json(['message' => 'اسناد حسابداری']));
        Route::middleware('permission:accounting.create')->post('/accounting/journal', fn () => response()->json(['message' => 'ثبت سند']));

        Route::middleware('permission:inventory.view')->get('/inventory/stock', fn () => response()->json(['message' => 'موجودی انبار']));
        Route::middleware('permission:inventory.create')->post('/inventory/transactions', fn () => response()->json(['message' => 'ثبت تراکنش']));

        Route::middleware('permission:invoices.view')->get('/invoices', fn () => response()->json(['message' => 'لیست فاکتورها']));
        Route::middleware('permission:invoices.create')->post('/invoices', fn () => response()->json(['message' => 'صدور فاکتور']));

        Route::middleware('permission:reports.view')->get('/reports/balance-sheet', fn () => response()->json(['message' => 'ترازنامه']));
    });
});

// ---------- مسیر سلامت ----------
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => '1.0.0',
    ]);
});
