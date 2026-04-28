<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/payments', [DashboardController::class, 'payments'])->name('admin.payments');
        Route::post('/tenants/{tenant}/toggle-sync', [DashboardController::class, 'toggleSync'])->name('admin.tenants.toggle-sync');
        Route::put('/users/{user}', [DashboardController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/users/{user}', [DashboardController::class, 'deleteUser'])->name('admin.users.delete');
    });
});
