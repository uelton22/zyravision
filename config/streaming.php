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
