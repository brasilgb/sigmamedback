<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\FeedbackController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\Sync\BloodPressureSyncController;
use App\Http\Controllers\Api\V1\Sync\GlicoseSyncController;
use App\Http\Controllers\Api\V1\Sync\MedicationLogSyncController;
use App\Http\Controllers\Api\V1\Sync\MedicationSyncController;
use App\Http\Controllers\Api\V1\Sync\WeightSyncController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('v1/webhooks/mercadopago', [WebhookController::class, 'mercadopago']);

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::patch('me', [AuthController::class, 'update']);
            Route::delete('me', [AuthController::class, 'destroy']);

            Route::middleware(['tenant'])->group(function () {
                Route::post('me/avatar', [AuthController::class, 'uploadAvatar']);
                Route::delete('me/avatar', [AuthController::class, 'destroyAvatar']);
            });
        });
    });

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::get('profile', [ProfileController::class, 'show']);
        Route::patch('profile', [ProfileController::class, 'update']);
        Route::get('profiles', [ProfileController::class, 'index']);
        Route::post('profiles', [ProfileController::class, 'store']);
        Route::post('feedback', [FeedbackController::class, 'store']);

        Route::prefix('billing')->group(function () {
            Route::get('sync-access', [BillingController::class, 'syncAccess']);
            Route::post('sync-access/checkout', [BillingController::class, 'checkout']);
        });

        Route::middleware(['sync_enabled'])->group(function () {
            Route::post('blood-pressure/sync', [BloodPressureSyncController::class, 'sync']);
            Route::post('glicose/sync', [GlicoseSyncController::class, 'sync']);
            Route::post('weight/sync', [WeightSyncController::class, 'sync']);
            Route::post('medications/sync', [MedicationSyncController::class, 'sync']);
            Route::post('medication-logs/sync', [MedicationLogSyncController::class, 'sync']);
            Route::post('sync/push', [SyncController::class, 'push']);
            Route::post('sync/pull', [SyncController::class, 'pull']);
        });
    });
});
