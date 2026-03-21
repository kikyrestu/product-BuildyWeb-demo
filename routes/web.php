<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/install', fn () => redirect()->route('login'))->name('install.show');
Route::post('/install', fn () => redirect()->route('login'))->name('install.process');
Route::post('/install/test', fn () => redirect()->route('login'))->name('install.test');

Route::get('/_ops/deploy', function (Request $request) {
    $deployToken = (string) env('DEPLOY_TOKEN', '');

    if ($deployToken === '') {
        abort(404);
    }

    if (! hash_equals($deployToken, (string) $request->query('token'))) {
        abort(403);
    }

    $clearExitCode = Artisan::call('optimize:clear');
    $clearOutput = trim(Artisan::output());

    $migrateExitCode = Artisan::call('migrate', ['--force' => true]);
    $migrateOutput = trim(Artisan::output());

    return response()->json([
        'ok' => $clearExitCode === 0 && $migrateExitCode === 0,
        'commands' => [
            'optimize_clear' => [
                'exit_code' => $clearExitCode,
                'output' => $clearOutput,
            ],
            'migrate_force' => [
                'exit_code' => $migrateExitCode,
                'output' => $migrateOutput,
            ],
        ],
        'next_step' => 'Set DEPLOY_TOKEN to empty after success to disable this endpoint.',
    ]);
})->name('ops.deploy');

Route::middleware('installed')->group(function (): void {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/invoice/{transaction}', [PanelController::class, 'invoice'])
        ->middleware('signed')
        ->name('orders.invoice');

    Route::get('/progress/{transaction}', [PanelController::class, 'progress'])
        ->middleware('signed')
        ->name('orders.progress');

    Route::get('/invoice/{transaction}/download', [PanelController::class, 'downloadInvoicePdf'])
        ->middleware('signed')
        ->name('orders.invoice.download');

    Route::get('/dashboard', DashboardController::class)
        ->middleware(['auth', 'verified', 'role:owner,admin'])
        ->name('dashboard');

    Route::middleware(['auth', 'role:owner,admin,kasir'])->group(function (): void {
        Route::get('/pos', [PanelController::class, 'pos'])->name('pos.index');
        Route::post('/pos/checkout', [PanelController::class, 'checkoutPos'])->name('pos.checkout');
        Route::get('/orders/tracking', [PanelController::class, 'tracking'])->name('orders.tracking');
        Route::get('/orders/{transaction}', [PanelController::class, 'orderDetail'])->name('orders.show');
        Route::post('/orders/{transaction}/customer', [PanelController::class, 'updateOrderCustomer'])->name('orders.customer.update');
        Route::delete('/orders/{transaction}/customer', [PanelController::class, 'removeOrderCustomer'])->name('orders.customer.remove');
        Route::get('/orders/{transaction}/send-wa', [PanelController::class, 'sendOrderWa'])->name('orders.send-wa');
        Route::post('/orders/{transaction}/advance-status', [PanelController::class, 'advanceTrackingStatus'])->name('orders.advance-status');
        Route::post('/orders/{transaction}/status', [PanelController::class, 'setTrackingStatus'])->name('orders.status.set');
        Route::post('/orders/{transaction}/payment', [PanelController::class, 'updateTrackingPayment'])->name('orders.payment.update');
        Route::get('/media/public/{path}', [PanelController::class, 'showPublicMedia'])->where('path', '.*')->name('media.public');
    });

    Route::middleware(['auth', 'role:owner,admin'])->group(function (): void {
        Route::get('/master', [PanelController::class, 'master'])->name('master.index');
        Route::post('/master', [PanelController::class, 'storeMasterItem'])->name('master.store');
        Route::get('/reports/financial', [PanelController::class, 'reports'])->name('reports.financial');
        Route::get('/reports/financial/export', [PanelController::class, 'exportFinancialExcel'])->name('reports.financial.export');
    });

    Route::middleware(['auth', 'role:owner'])->group(function (): void {
        Route::get('/settings/installer/reset', [InstallerController::class, 'resetForm'])->name('settings.installer.reset.form');
        Route::post('/settings/installer/reset', [InstallerController::class, 'reset'])->name('settings.installer.reset');
        Route::get('/settings/laundry-profile', [PanelController::class, 'laundryProfileSettings'])->name('settings.laundry-profile');
        Route::put('/settings/laundry-profile', [PanelController::class, 'updateLaundryProfileSettings'])->name('settings.laundry-profile.update');
        Route::get('/settings/wa-templates', [PanelController::class, 'waTemplates'])->name('settings.wa-templates');
        Route::put('/settings/wa-templates', [PanelController::class, 'updateWaTemplates'])->name('settings.wa-templates.update');
        Route::get('/settings/payment-options', [PanelController::class, 'paymentOptions'])->name('settings.payment-options');
        Route::post('/settings/payment-options', [PanelController::class, 'storePaymentOption'])->name('settings.payment-options.store');
        Route::put('/settings/payment-options/{paymentOption}', [PanelController::class, 'updatePaymentOption'])->name('settings.payment-options.update');
        Route::delete('/settings/payment-options/{paymentOption}', [PanelController::class, 'destroyPaymentOption'])->name('settings.payment-options.destroy');
        Route::get('/settings/users', [PanelController::class, 'userManagement'])->name('settings.users');
        Route::post('/settings/users', [PanelController::class, 'storeUser'])->name('settings.users.store');
        Route::put('/settings/users/{user}', [PanelController::class, 'updateUserAccess'])->name('settings.users.update');
        Route::delete('/settings/users/{user}', [PanelController::class, 'destroyUser'])->name('settings.users.destroy');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__.'/auth.php';
});
