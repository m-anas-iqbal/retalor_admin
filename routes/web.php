<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ApiUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.store')->middleware('throttle:10,1');
        Route::get('forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email')->middleware('throttle:3,5');
        Route::get('reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:10,10');
    });

    Route::middleware('auth:admin')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('admins', AdminUserController::class)->except('show');
        Route::resource('api-users', ApiUserController::class)->parameters(['api-users' => 'user'])->except('show');
        Route::resource('plans', PlanController::class)->except('show');
        Route::resource('subscriptions', SubscriptionController::class)->except('show');
        Route::get('subscription-payments', [SubscriptionPaymentController::class, 'index'])->name('subscription-payments.index');
        Route::get('subscription-payments/{subscriptionPayment}/edit', [SubscriptionPaymentController::class, 'edit'])->name('subscription-payments.edit');
        Route::post('subscription-payments/{subscriptionPayment}/approve', [SubscriptionPaymentController::class, 'approve'])->name('subscription-payments.approve');
        Route::post('subscription-payments/{subscriptionPayment}/reject', [SubscriptionPaymentController::class, 'reject'])->name('subscription-payments.reject');
        Route::get('subscription-payments/{subscriptionPayment}/download', [SubscriptionPaymentController::class, 'download'])->name('subscription-payments.download');
    });
});
