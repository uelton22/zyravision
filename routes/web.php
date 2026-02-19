<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\RecordingController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\StorageBackendController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\UserDeviceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — ZyraVision
|--------------------------------------------------------------------------
*/

// ─── Public (Landing Page) ────────────────────────────────
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/demo', [DemoController::class, 'redirect'])->name('demo');

// ─── Auth ─────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // ═══════════════════════════════════════════════════════
    // ─── Todos os usuarios autenticados ───────────────────
    // ═══════════════════════════════════════════════════════

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ═══════════════════════════════════════════════════════
    // ─── Admin + Super-admin ──────────────────────────────
    // ─── (cameras CRUD, settings, users, devices, etc)  ───
    // ─── IMPORTANT: Rotas especificas ANTES de {wildcard} ─
    // ═══════════════════════════════════════════════════════

    Route::middleware('role:admin|super-admin')->group(function () {
        // Camera CRUD (create, edit, delete) — definido ANTES de /cameras/{camera}
        Route::get('/cameras/create', [CameraController::class, 'create'])->name('cameras.create');
        Route::post('/cameras', [CameraController::class, 'store'])->name('cameras.store');
        Route::get('/cameras/{camera}/edit', [CameraController::class, 'edit'])->name('cameras.edit');
        Route::put('/cameras/{camera}', [CameraController::class, 'update'])->name('cameras.update');
        Route::delete('/cameras/{camera}', [CameraController::class, 'destroy'])->name('cameras.destroy');

        // Devices CRUD
        Route::resource('devices', DeviceController::class);

        // Recording management
        Route::post('/cameras/{camera}/recording/start', [RecordingController::class, 'start'])->name('cameras.recording.start');
        Route::post('/cameras/{camera}/recording/stop', [RecordingController::class, 'stop'])->name('cameras.recording.stop');
        Route::get('/cameras/{camera}/recording', [RecordingController::class, 'status'])->name('cameras.recording.status');
        Route::delete('/recordings/{recording}', [RecordingController::class, 'destroy'])->name('recordings.destroy');
        Route::post('/recordings/{recording}/clip', [RecordingController::class, 'clip'])->name('recordings.clip');

        // Storage dashboard (view usage)
        // Route path uses /armazenamento to avoid conflict with Laravel's public/storage symlink
        Route::get('/armazenamento', [StorageController::class, 'index'])->name('storage.index');
        Route::put('/armazenamento/retention', [StorageController::class, 'updateRetention'])->name('storage.retention.update');

        // Users CRUD
        Route::resource('users', UserController::class);

        // Audit Log
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    });

    // ═══════════════════════════════════════════════════════
    // ─── Super-admin only ────────────────────────────────
    // ─── (configuracoes do sistema)                       ─
    // ═══════════════════════════════════════════════════════

    Route::middleware('role:super-admin')->group(function () {
        // Settings: Recording Configuration
        Route::prefix('settings/recording')->name('settings.recording.')->group(function () {
            Route::get('/', [\App\Http\Controllers\RecordingSettingsController::class, 'index'])->name('index');
            Route::put('/', [\App\Http\Controllers\RecordingSettingsController::class, 'update'])->name('update');
        });

        // Settings: API Tokens
        Route::get('/settings/api-tokens', [ApiTokenController::class, 'index'])->name('settings.api-tokens.index');
        Route::post('/settings/api-tokens', [ApiTokenController::class, 'store'])->name('settings.api-tokens.store');
        Route::delete('/settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('settings.api-tokens.destroy');

        // Settings: API Documentation
        Route::get('/settings/api/docs', [\App\Http\Controllers\ApiDocController::class, 'index'])->name('settings.api-docs.index');

        // Settings: Connected Devices
        Route::get('/settings/devices', [UserDeviceController::class, 'index'])->name('settings.devices.index');
        Route::put('/settings/devices/{device}/revoke', [UserDeviceController::class, 'revoke'])->name('settings.devices.revoke');
        Route::delete('/settings/devices/{device}', [UserDeviceController::class, 'destroy'])->name('settings.devices.destroy');

        // Settings: System Maintenance (cache clearing)
        Route::get('/settings/system', [\App\Http\Controllers\SystemSettingsController::class, 'index'])->name('settings.system.index');
        Route::post('/settings/system/clear-segment-cache', [\App\Http\Controllers\SystemSettingsController::class, 'clearSegmentCache'])->name('settings.system.clear-segment-cache');
        Route::post('/settings/system/clear-app-cache', [\App\Http\Controllers\SystemSettingsController::class, 'clearAppCache'])->name('settings.system.clear-app-cache');

        // Settings: Workers Dashboard (monitoramento de workers)
        Route::get('/settings/workers', [\App\Http\Controllers\WorkerDashboardController::class, 'index'])->name('settings.workers.index');
        Route::get('/settings/workers/api', [\App\Http\Controllers\WorkerDashboardController::class, 'api'])->name('settings.workers.api');
    });

    // ═══════════════════════════════════════════════════════
    // ─── Viewer + Operator + Admin + Super-admin ──────────
    // ─── (cameras.view, cameras.stream, recordings.view) ──
    // ═══════════════════════════════════════════════════════

    Route::middleware('permission:cameras.view')->group(function () {
        Route::get('/cameras', [CameraController::class, 'index'])->name('cameras.index');
        Route::get('/cameras/{camera}', [CameraController::class, 'show'])->name('cameras.show');
        Route::get('/cameras/{camera}/recordings', [CameraController::class, 'recordings'])->name('cameras.recordings');
    });

    Route::middleware('permission:cameras.stream')->group(function () {
        Route::post('/cameras/{camera}/stream/start', [StreamController::class, 'start'])->name('cameras.stream.start');
        Route::post('/cameras/{camera}/stream/stop', [StreamController::class, 'stop'])->name('cameras.stream.stop');
        Route::get('/cameras/{camera}/stream', [StreamController::class, 'status'])->name('cameras.stream.status');
    });

    Route::middleware('permission:recordings.view')->group(function () {
        Route::get('/recordings', [RecordingController::class, 'index'])->name('recordings.index');
        Route::get('/recordings/{recording}', [RecordingController::class, 'show'])->name('recordings.show');
        Route::get('/recordings/{recording}/playback', [RecordingController::class, 'playback'])->name('recordings.playback');
        Route::get('/recordings/{recording}/stream', [RecordingController::class, 'stream'])->name('recordings.stream');

        // Segment timeline (needed for web playback UI)
        Route::get('/api/cameras/{camera}/timeline', [\App\Http\Controllers\SegmentController::class, 'timeline'])->name('api.cameras.timeline');
        Route::get('/api/cameras/{camera}/timeline/dates', [\App\Http\Controllers\SegmentController::class, 'availableDates'])->name('api.cameras.timeline.dates');
        Route::get('/api/cameras/{camera}/playback', [\App\Http\Controllers\SegmentController::class, 'playbackAtTime'])->name('api.cameras.playback');
        Route::get('/api/cameras/{camera}/playback.m3u8', [\App\Http\Controllers\SegmentController::class, 'hlsPlaylist'])->name('cameras.hls-playlist');
    });

    // ═══════════════════════════════════════════════════════
    // ─── Operator + Admin + Super-admin ───────────────────
    // ─── (recordings.download, analytics.view) ────────────
    // ═══════════════════════════════════════════════════════

    Route::middleware('permission:recordings.download')->group(function () {
        Route::get('/recordings/{recording}/download', [RecordingController::class, 'download'])->name('recordings.download');
        Route::get('/api/segments/{segment}/download', [\App\Http\Controllers\SegmentController::class, 'download'])->name('segments.download');
    });

    Route::middleware('permission:analytics.view')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    });

    // ═══════════════════════════════════════════════════════
    // ─── Super-admin only ─────────────────────────────────
    // ═══════════════════════════════════════════════════════

    Route::middleware('role:super-admin')->group(function () {
        // Tenants & Plans
        Route::resource('tenants', TenantController::class);
        Route::put('/tenants/{tenant}/change-plan', [TenantController::class, 'changePlan'])->name('tenants.change-plan');
        Route::resource('plans', PlanController::class);

        // Settings: Storage Backends (criar/editar/excluir backends)
        Route::prefix('settings/storage')->name('settings.storage.')->group(function () {
            Route::get('/', [StorageBackendController::class, 'index'])->name('index');
            Route::post('/', [StorageBackendController::class, 'store'])->name('store');
            Route::put('/{backend}', [StorageBackendController::class, 'update'])->name('update');
            Route::delete('/{backend}', [StorageBackendController::class, 'destroy'])->name('destroy');
            Route::post('/{backend}/test', [StorageBackendController::class, 'test'])->name('test');
            Route::post('/{backend}/set-default', [StorageBackendController::class, 'setDefault'])->name('set-default');
        });
    });
});

// Segment streaming (web route): supports session auth for web UI
Route::middleware([\App\Http\Middleware\TokenFromQueryString::class, 'auth:sanctum'])->group(function () {
    Route::get('/api/segments/{segment}/stream', [\App\Http\Controllers\SegmentController::class, 'stream'])->name('segments.stream');
});
