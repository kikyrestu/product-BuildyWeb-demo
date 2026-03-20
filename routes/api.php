<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\MasterItemController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Controllers\Api\V1\VoucherController;
use App\Http\Controllers\Api\V1\WaTemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::prefix('master')->group(function (): void {
        Route::get('services', [MasterItemController::class, 'services']);
        Route::get('durations', [MasterItemController::class, 'durations']);
        Route::get('addons', [MasterItemController::class, 'addons']);
    });

    Route::post('vouchers/validate', [VoucherController::class, 'validateVoucher']);
    Route::get('tracking/{receipt_number}', [TrackingController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::prefix('orders')->group(function (): void {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('board', [OrderController::class, 'board']);
            Route::get('summary/statuses', [OrderController::class, 'statusSummary']);
            Route::get('{transaction}', [OrderController::class, 'show']);
            Route::post('calculate', [OrderController::class, 'calculate']);
            Route::post('checkout', [OrderController::class, 'checkout']);
            Route::post('{transaction}/wa-preview', [OrderController::class, 'waPreview']);
            Route::patch('{transaction}/payment-status', [OrderController::class, 'updatePaymentStatus']);
            Route::patch('{transaction}/status', [OrderController::class, 'updateStatus']);
        });

        Route::prefix('customers')->group(function (): void {
            Route::get('/', [CustomerController::class, 'index']);
            Route::post('/', [CustomerController::class, 'store']);
            Route::get('{customer}', [CustomerController::class, 'show']);
            Route::put('{customer}', [CustomerController::class, 'update']);
        });

        Route::middleware('role:owner,admin')->group(function (): void {
            Route::prefix('settings')->group(function (): void {
                Route::get('wa-templates', [WaTemplateController::class, 'index']);
                Route::put('wa-templates', [WaTemplateController::class, 'upsert']);
            });

            Route::post('master/items', [MasterItemController::class, 'store']);
            Route::put('master/items/{masterItem}', [MasterItemController::class, 'update']);

            Route::get('reports/financial', [ReportController::class, 'financial']);
        });
    });
});
