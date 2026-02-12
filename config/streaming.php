<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MediaMTX Configuration
    |--------------------------------------------------------------------------
    */

    'mediamtx' => [
        'host' => env('MEDIAMTX_HOST', 'mediamtx'),
        'api_port' => (int) env('MEDIAMTX_API_PORT', 9997),
        'rtsp_port' => (int) env('MEDIAMTX_RTSP_PORT', 8554),
        'hls_port' => (int) env('MEDIAMTX_HLS_PORT', 8888),
        'webrtc_port' => (int) env('MEDIAMTX_WEBRTC_PORT', 8889),
        'api_url' => env('MEDIAMTX_API_URL', 'http://mediamtx:9997'),
        'api_user' => env('MEDIAMTX_API_USER', 'zyravision'),
        'api_pass' => env('MEDIAMTX_API_PASS', 'zyravision_api_secret'),
        'timeout' => (int) env('MEDIAMTX_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | HLS Configuration
    |--------------------------------------------------------------------------
    */

    'hls' => [
        'base_url' => env('HLS_BASE_URL', '/hls'),
        'segment_duration' => 1,
        'segment_count' => 7,
        'variant' => 'lowLatency',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stream Limits
    |--------------------------------------------------------------------------
    */

    'limits' => [
        'max_concurrent_streams' => (int) env('MAX_CONCURRENT_STREAMS', 50),
        'reconnect_attempts' => (int) env('STREAM_RECONNECT_ATTEMPTS', 10),
        'reconnect_delay_seconds' => (int) env('STREAM_RECONNECT_DELAY', 10),
        'health_check_interval' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Recovery Configuration (Self-Healing)
    |--------------------------------------------------------------------------
    |
    | Controls how the system automatically recovers from stream failures.
    | The system NEVER permanently gives up — it uses progressive strategies:
    |
    | Phase 1 (Soft Recovery): Re-register path or wait for MediaMTX reconnect
    |   - Fast backoff (30s–120s) for transient network issues
    |
    | Phase 2 (Hard Reset): Remove path + re-add (like manual "Force Reset")
    |   - Triggered after soft_attempts_before_hard_reset soft failures
    |   - Clears any stuck state in MediaMTX
    |
    | Phase 3 (Maintenance Mode): Slow periodic hard resets indefinitely
    |   - Runs every maintenance_interval_seconds
    |   - Keeps trying until camera comes back online
    |   - No human intervention required
    |
    */

    'recovery' => [
        // Phase 1: Soft recovery (re-register path, wait for MediaMTX)
        // Backoff: 30s, 45s, 60s, 90s, 120s (max)
        'soft_max_attempts' => (int) env('STREAM_SOFT_MAX_ATTEMPTS', 5),
        'soft_initial_backoff_seconds' => 30,
        'soft_max_backoff_seconds' => 120,

        // Phase 2: Hard reset (remove + re-add path, like "Force Reset")
        // How many soft attempts must fail before trying a hard reset
        'soft_attempts_before_hard_reset' => (int) env('STREAM_SOFT_BEFORE_HARD_RESET', 3),
        'hard_max_attempts' => (int) env('STREAM_HARD_MAX_ATTEMPTS', 3),
        'hard_reset_cooldown_seconds' => 60,

        // Phase 3: Maintenance mode (slow periodic hard resets, never gives up)
        'maintenance_interval_seconds' => (int) env('STREAM_MAINTENANCE_INTERVAL', 300),

        // How many monitor checks (30s each) a path can be "not ready"
        // before marking as error and triggering recovery
        'not_ready_checks_before_error' => (int) env('STREAM_NOT_READY_CHECKS', 6),

        // Delay in seconds after removing a path before re-adding it
        'hard_reset_delay_seconds' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Snapshot Configuration
    |--------------------------------------------------------------------------
    */

    'snapshots' => [
        'interval_minutes' => (int) env('SNAPSHOT_INTERVAL_MINUTES', 5),
        'storage_path' => 'public/snapshots',
        'quality' => 80,
    ],

    /*
    |--------------------------------------------------------------------------
    | Recording Configuration
    |--------------------------------------------------------------------------
    */

    'recording' => [
        'enabled' => env('RECORDING_ENABLED', true),
        'format' => 'fmp4',
        'segment_duration' => '5m',
        'base_path' => env('RECORDING_BASE_PATH', '/recordings'),
        'default_retention_days' => (int) env('RECORDING_RETENTION_DAYS', 30),
        'scan_interval_seconds' => 60,
        'cleanup_after_upload' => env('RECORDING_CLEANUP_AFTER_UPLOAD', false),
    ],

];
