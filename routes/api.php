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

        // Users
        Route::apiResource('users', \App\Http\Controllers\Api\V1\UserController::class);

        // Tenants (super-admin only)
        Route::middleware('role:super-admin')->group(function () {
            Route::apiResource('tenants', \App\Http\Controllers\Api\V1\TenantController::class);
        });
    });
});
