<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — ZyraVision
|--------------------------------------------------------------------------
|
| Rotas da API REST versão 1.
| Autenticação via Laravel Sanctum.
| Multi-tenant via middleware 'tenant'.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // ─── Public ───────────────────────────────────────────
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::post('/auth/register', [\App\Http\Controllers\Api\V1\AuthController::class, 'register']);
    Route::post('/auth/forgot-password', [\App\Http\Controllers\Api\V1\AuthController::class, 'forgotPassword']);

    // ─── Landing Page Contact ─────────────────────────────
    Route::post('/contact', [\App\Http\Controllers\Api\V1\ContactController::class, 'store']);

    // ─── Authenticated ────────────────────────────────────
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        // Auth
        Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
        Route::get('/auth/me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);

        // Tokens
        Route::get('/tokens', [\App\Http\Controllers\Api\V1\TokenController::class, 'index']);
        Route::post('/tokens', [\App\Http\Controllers\Api\V1\TokenController::class, 'store']);
        Route::delete('/tokens/{token}', [\App\Http\Controllers\Api\V1\TokenController::class, 'destroy']);
        Route::get('/tokens/abilities', [\App\Http\Controllers\Api\V1\TokenController::class, 'abilities']);

        // Profile
        Route::get('/profile', [\App\Http\Controllers\Api\V1\ProfileController::class, 'show']);
        Route::put('/profile', [\App\Http\Controllers\Api\V1\ProfileController::class, 'update']);
        Route::put('/profile/password', [\App\Http\Controllers\Api\V1\ProfileController::class, 'updatePassword']);

        // Cameras — static routes BEFORE apiResource to avoid {camera} capture
        Route::get('/cameras/recordings-summary', [\App\Http\Controllers\SegmentController::class, 'camerasWithRecordings']);
        Route::apiResource('cameras', \App\Http\Controllers\Api\V1\CameraController::class);
        Route::get('/cameras/{camera}/thumbnail', [\App\Http\Controllers\Api\V1\CameraController::class, 'thumbnail']);
        Route::get('/cameras/{camera}/recordings', [\App\Http\Controllers\Api\V1\RecordingController::class, 'cameraRecordings']);

        // Devices
        Route::apiResource('devices', \App\Http\Controllers\Api\V1\DeviceController::class);

        // Streams
        Route::post('/cameras/{camera}/stream/start', [\App\Http\Controllers\Api\V1\StreamController::class, 'start']);
        Route::post('/cameras/{camera}/stream/stop', [\App\Http\Controllers\Api\V1\StreamController::class, 'stop']);
        Route::get('/cameras/{camera}/stream', [\App\Http\Controllers\Api\V1\StreamController::class, 'status']);

        // Recordings
        Route::get('/recordings', [\App\Http\Controllers\Api\V1\RecordingController::class, 'index']);
        Route::get('/recordings/{recording}', [\App\Http\Controllers\Api\V1\RecordingController::class, 'show']);
        Route::delete('/recordings/{recording}', [\App\Http\Controllers\Api\V1\RecordingController::class, 'destroy']);
        Route::get('/recordings/{recording}/stream', [\App\Http\Controllers\Api\V1\RecordingController::class, 'stream']);
        Route::get('/recordings/{recording}/download', [\App\Http\Controllers\Api\V1\RecordingController::class, 'download']);
        Route::post('/recordings/{recording}/clip', [\App\Http\Controllers\Api\V1\RecordingController::class, 'clip']);

        // Segments & Timeline (playback)
        Route::get('/cameras/{camera}/timeline', [\App\Http\Controllers\SegmentController::class, 'timeline']);
        Route::get('/cameras/{camera}/timeline/dates', [\App\Http\Controllers\SegmentController::class, 'availableDates']);
        Route::get('/cameras/{camera}/playback', [\App\Http\Controllers\SegmentController::class, 'playbackAtTime']);
        Route::get('/segments/{segment}/stream', [\App\Http\Controllers\SegmentController::class, 'stream']);
        Route::get('/segments/{segment}/download', [\App\Http\Controllers\SegmentController::class, 'download']);

        // Storage
        Route::get('/storage', [\App\Http\Controllers\Api\V1\StorageController::class, 'index']);
        Route::put('/storage/retention', [\App\Http\Controllers\Api\V1\StorageController::class, 'updateRetention']);

        // Plans (read-only)
        Route::get('/plans', [\App\Http\Controllers\Api\V1\PlanController::class, 'index']);
        Route::get('/plans/{plan}', [\App\Http\Controllers\Api\V1\PlanController::class, 'show']);

        // Mobile
        Route::prefix('mobile')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\V1\MobileController::class, 'dashboard']);
            Route::get('/sync/status', [\App\Http\Controllers\Api\V1\MobileController::class, 'syncStatus']);
            Route::post('/sync/pull', [\App\Http\Controllers\Api\V1\MobileController::class, 'syncPull']);
            Route::post('/sync/push', [\App\Http\Controllers\Api\V1\MobileController::class, 'syncPush']);
        });

        // User Devices (mobile device registration & push tokens)
        Route::prefix('user-devices')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\DeviceRegistrationController::class, 'index']);
            Route::post('/register', [\App\Http\Controllers\Api\V1\DeviceRegistrationController::class, 'register']);
            Route::put('/{device}/push-token', [\App\Http\Controllers\Api\V1\DeviceRegistrationController::class, 'updatePushToken']);
            Route::delete('/{device}', [\App\Http\Controllers\Api\V1\DeviceRegistrationController::class, 'revoke']);
        });

        // System Workers (monitoramento de workers — admin+)
        Route::middleware('role:admin|super-admin')->group(function () {
            Route::get('/system/workers', [\App\Http\Controllers\WorkerDashboardController::class, 'api'])->name('system.workers');
        });

        // Users
        Route::apiResource('users', \App\Http\Controllers\Api\V1\UserController::class);

        // Tenants (super-admin only)
        Route::middleware('role:super-admin')->group(function () {
            Route::apiResource('tenants', \App\Http\Controllers\Api\V1\TenantController::class);
        });
    });
});
