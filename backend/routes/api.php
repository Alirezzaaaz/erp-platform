<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\PartyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ============ عمومی ============
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:register');

    // ============ محافظت‌شده ============
    Route::middleware(['auth:api', 'tenant'])->group(function () {

        // --- احراز هویت ---
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
        });

        // --- کالاها ---
        Route::prefix('products')->group(function () {
            Route::middleware('permission:products.view')->group(function () {
                Route::get('/', [ProductController::class, 'index']);
                Route::get('/{product}', [ProductController::class, 'show']);
            });
            Route::middleware('permission:products.create')->post('/', [ProductController::class, 'store']);
            Route::middleware('permission:products.edit')->group(function () {
                Route::put('/{product}', [ProductController::class, 'update']);
                Route::patch('/{product}', [ProductController::class, 'update']);
                Route::patch('/{product}/toggle-active', [ProductController::class, 'toggleActive']);
            });
            Route::middleware('permission:products.delete')->delete('/{product}', [ProductController::class, 'destroy']);
        });

        // --- انبارها ---
        Route::prefix('warehouses')->group(function () {
            Route::middleware('permission:warehouses.view')->group(function () {
                Route::get('/', [WarehouseController::class, 'index']);
                Route::get('/{warehouse}', [WarehouseController::class, 'show']);
            });
            Route::middleware('permission:warehouses.create')->post('/', [WarehouseController::class, 'store']);
            Route::middleware('permission:warehouses.edit')->group(function () {
                Route::put('/{warehouse}', [WarehouseController::class, 'update']);
                Route::patch('/{warehouse}', [WarehouseController::class, 'update']);
            });
            Route::middleware('permission:warehouses.delete')->delete('/{warehouse}', [WarehouseController::class, 'destroy']);
        });

        // --- اشخاص (مشتریان/تأمین‌کنندگان) ---
        Route::prefix('parties')->group(function () {
            Route::middleware('permission:parties.view')->group(function () {
                Route::get('/', [PartyController::class, 'index']);
                Route::get('/{party}', [PartyController::class, 'show']);
            });
            Route::middleware('permission:parties.create')->post('/', [PartyController::class, 'store']);
            Route::middleware('permission:parties.edit')->group(function () {
                Route::put('/{party}', [PartyController::class, 'update']);
                Route::patch('/{party}', [PartyController::class, 'update']);
            });
            Route::middleware('permission:parties.delete')->delete('/{party}', [PartyController::class, 'destroy']);
        });

        // --- دسته‌بندی کالاها ---
        Route::prefix('product-categories')->group(function () {
            Route::middleware('permission:products.view')->group(function () {
                Route::get('/', [ProductCategoryController::class, 'index']);
                Route::get('/{product_category}', [ProductCategoryController::class, 'show']);
            });
            Route::middleware('permission:products.create')->post('/', [ProductCategoryController::class, 'store']);
            Route::middleware('permission:products.edit')->group(function () {
                Route::put('/{product_category}', [ProductCategoryController::class, 'update']);
                Route::patch('/{product_category}', [ProductCategoryController::class, 'update']);
            });
            Route::middleware('permission:products.delete')->delete('/{product_category}', [ProductCategoryController::class, 'destroy']);
        });

        // --- واحدهای اندازه‌گیری ---
        Route::prefix('units')->group(function () {
            Route::middleware('permission:products.view')->group(function () {
                Route::get('/', [UnitController::class, 'index']);
                Route::get('/{unit}', [UnitController::class, 'show']);
            });
            Route::middleware('permission:products.create')->post('/', [UnitController::class, 'store']);
            Route::middleware('permission:products.edit')->group(function () {
                Route::put('/{unit}', [UnitController::class, 'update']);
                Route::patch('/{unit}', [UnitController::class, 'update']);
            });
            Route::middleware('permission:products.delete')->delete('/{unit}', [UnitController::class, 'destroy']);
        });

        // --- حسابداری (placeholder) ---
        Route::middleware('permission:accounting.view')->get('/accounting/journal', fn () => response()->json(['message' => 'Sprint بعدی']));
        Route::middleware('permission:inventory.view')->get('/inventory/stock', fn () => response()->json(['message' => 'Sprint بعدی']));
        Route::middleware('permission:invoices.view')->get('/invoices', fn () => response()->json(['message' => 'Sprint بعدی']));
        Route::middleware('permission:reports.view')->get('/reports/balance-sheet', fn () => response()->json(['message' => 'Sprint بعدی']));

        // --- نقشه انبار ---
        Route::middleware(['platform:windows', 'role:admin'])->prefix('warehouse-map')->group(function () {
            Route::post('/layouts', fn () => response()->json(['message' => 'Sprint بعدی']));
            Route::put('/layouts/{id}', fn () => response()->json(['message' => 'Sprint بعدی']));
            Route::post('/layouts/{id}/publish', fn () => response()->json(['message' => 'Sprint بعدی']));
        });
        Route::get('/warehouse-map/layouts/{id}', fn () => response()->json(['message' => 'مشاهده نقشه']));
    });
});

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
    'version' => '1.0.0',
]));
