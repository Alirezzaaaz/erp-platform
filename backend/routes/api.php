<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\JournalEntryController;
use App\Http\Controllers\Api\V1\PartyController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\TaxController;
use App\Http\Controllers\Api\V1\ThemeController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\WarehouseLayoutController;
use App\Http\Controllers\Api\V1\WarehouseLocationController;
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

        // --- اشخاص ---
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

        // --- دسته‌بندی کالا ---
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

        // --- واحدها ---
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

        // --- انبارداری ---
        Route::prefix('inventory')->group(function () {
            Route::middleware('permission:inventory.view')->group(function () {
                Route::get('/stock', [InventoryController::class, 'stockList']);
                Route::get('/stock/product/{productId}', [InventoryController::class, 'productStock']);
                Route::get('/summary', [InventoryController::class, 'summary']);
                Route::get('/transactions', [InventoryController::class, 'transactions']);
            });
            Route::middleware('permission:inventory.create')->group(function () {
                Route::post('/stock-in', [InventoryController::class, 'stockIn']);
                Route::post('/stock-out', [InventoryController::class, 'stockOut']);
                Route::post('/transfer', [InventoryController::class, 'transfer']);
                Route::post('/adjust', [InventoryController::class, 'adjust']);
            });
        });

        // --- حسابداری ---
        Route::prefix('accounting')->group(function () {
            Route::middleware('permission:accounting.view')->group(function () {
                Route::get('/accounts', [AccountController::class, 'index']);
                Route::get('/accounts/{account}', [AccountController::class, 'show']);
                Route::get('/accounts/{accountId}/ledger', [AccountController::class, 'ledger']);
                Route::get('/journal-entries', [JournalEntryController::class, 'index']);
                Route::get('/journal-entries/{journalEntry}', [JournalEntryController::class, 'show']);
            });
            Route::middleware('permission:accounting.create')->group(function () {
                Route::post('/journal-entries', [JournalEntryController::class, 'store']);
                Route::post('/journal-entries/{journalEntry}/approve', [JournalEntryController::class, 'approve']);
                Route::post('/journal-entries/{journalEntry}/cancel', [JournalEntryController::class, 'cancel']);
            });
            Route::middleware('permission:accounting.edit')->group(function () {
                Route::put('/journal-entries/{journalEntry}', [JournalEntryController::class, 'update']);
                Route::patch('/journal-entries/{journalEntry}', [JournalEntryController::class, 'update']);
            });
            Route::middleware('permission:accounting.delete')->delete('/journal-entries/{journalEntry}', [JournalEntryController::class, 'destroy']);
        });

        // --- فاکتورها ---
        Route::prefix('invoices')->group(function () {
            Route::middleware('permission:invoices.view')->group(function () {
                Route::get('/', [InvoiceController::class, 'index']);
                Route::get('/{invoice}', [InvoiceController::class, 'show']);
            });
            Route::middleware('permission:invoices.create')->group(function () {
                Route::post('/', [InvoiceController::class, 'store']);
                Route::post('/{invoice}/confirm', [InvoiceController::class, 'confirm']);
                Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel']);
            });
            Route::middleware('permission:invoices.edit')->delete('/{invoice}', [InvoiceController::class, 'destroy']);
        });

        // --- گزارش‌ها ---
        Route::prefix('reports')->middleware('permission:reports.view')->group(function () {
            Route::get('/trial-balance', [ReportController::class, 'trialBalance']);
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet']);
            Route::get('/income-statement', [ReportController::class, 'incomeStatement']);
        });

        // --- سامانه مودیان ---
        Route::prefix('tax')->group(function () {
            Route::middleware('permission:invoices.view')->group(function () {
                Route::get('/invoices/{invoice}/status', [TaxController::class, 'queryStatus']);
                Route::get('/invoices/{invoice}/preview', [TaxController::class, 'previewPayload']);
            });
            Route::middleware('permission:invoices.create')->post('/invoices/{invoice}/send', [TaxController::class, 'sendInvoice']);
        });

        // --- SDUI Theme ---
        Route::prefix('theme')->group(function () {
            Route::get('/', [ThemeController::class, 'show']);
            Route::get('/version', [ThemeController::class, 'version']);
            Route::middleware('role:admin')->group(function () {
                Route::put('/', [ThemeController::class, 'update']);
                Route::patch('/', [ThemeController::class, 'update']);
                Route::post('/reset', [ThemeController::class, 'reset']);
            });
        });

        // --- Sync Engine ---
        Route::prefix('sync')->group(function () {
            Route::post('/register', [SyncController::class, 'register']);
            Route::post('/pull', [SyncController::class, 'pull']);
            Route::post('/push', [SyncController::class, 'push']);
            Route::get('/status', [SyncController::class, 'status']);
        });

        // ============ نقشه انبار ============
        Route::prefix('warehouse-map')->group(function () {
            // خواندن (همه پلتفرم‌ها)
            Route::middleware('permission:warehouse_map.view')->group(function () {
                Route::get('/layouts', [WarehouseLayoutController::class, 'index']);
                Route::get('/layouts/{layout}', [WarehouseLayoutController::class, 'show']);
                Route::get('/layouts/{layout}/stats', [WarehouseLayoutController::class, 'stats']);
                Route::get('/layouts/{layout}/locations', [WarehouseLocationController::class, 'index']);
                Route::get('/locations/{location}', [WarehouseLocationController::class, 'show']);
                Route::get('/warehouses/{warehouse}/published', [WarehouseLayoutController::class, 'published']);
            });

            // نوشتن (فقط ویندوز و admin/warehouse_designer)
            Route::middleware(['platform:windows', 'role:admin,warehouse_designer'])->group(function () {
                Route::middleware('permission:warehouse_map.create')->post('/layouts', [WarehouseLayoutController::class, 'store']);
                Route::middleware('permission:warehouse_map.edit')->group(function () {
                    Route::put('/layouts/{layout}', [WarehouseLayoutController::class, 'update']);
                    Route::patch('/layouts/{layout}', [WarehouseLayoutController::class, 'update']);
                    Route::post('/layouts/{layout}/publish', [WarehouseLayoutController::class, 'publish']);
                    Route::post('/layouts/{layout}/clone', [WarehouseLayoutController::class, 'clone']);

                    Route::post('/layouts/{layout}/locations', [WarehouseLocationController::class, 'store']);
                    Route::post('/layouts/{layout}/locations/bulk', [WarehouseLocationController::class, 'bulkStore']);
                    Route::put('/locations/{location}', [WarehouseLocationController::class, 'update']);
                    Route::patch('/locations/{location}', [WarehouseLocationController::class, 'update']);
                });
                Route::middleware('permission:warehouse_map.delete')->group(function () {
                    Route::delete('/layouts/{layout}', [WarehouseLayoutController::class, 'destroy']);
                    Route::delete('/locations/{location}', [WarehouseLocationController::class, 'destroy']);
                });
            });
        });
    });
});

// ============ سلامت ============
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
    'version' => '1.0.0',
]));
