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
    | Auto-Recovery Configuration (Self-Healing) — v2
    |--------------------------------------------------------------------------
    |
    | Monitoramento baseado em saude (inspirado no NUV):
    |
    | 1. Stream saudavel: ready=true OU bytesReceived aumentando
    | 2. Stream nao pronto: espera MediaMTX reconectar sozinho (tolerancia)
    | 3. Apos tolerancia: hard reset (remove + re-add path) — direto, sem editPath
    | 4. Hard resets agressivos a cada 90s ate 5x, depois modo manutencao (5 min)
    |
    | O SISTEMA NUNCA DESISTE — sempre tenta recuperar.
    |
    | TIMELINE: Not-ready → 90s → Hard Reset → 90s → HR#2 → 90s → HR#3 → ...
    |           Apos 5 HRs → 5min → HR#6 → 5min → HR#7 → ...
    |
    | DIFERENCIAIS vs v1:
    | - SEM editPath "soft recovery" (ineficaz e interfere no MediaMTX)
    | - SEM sleep() no job (nao bloqueia o worker)
    | - Camera marcada offline so apos 60s (nao imediatamente)
    | - Monitoramento por bytesReceived (detecta stale connections)
    | - readTimeout=30s no MediaMTX (mais tolerancia a hiccups)
    |
    */

    'recovery' => [
        // Tolerancia: segundos que o sistema espera MediaMTX reconectar sozinho
        // ANTES de fazer o primeiro hard reset. Cameras com rede instavel
        // se beneficiam de valores maiores. Padrao: 90s.
        'tolerance_before_reset_seconds' => (int) env('STREAM_TOLERANCE_SECONDS', 90),

        // Intervalo entre hard resets na fase agressiva (primeiros 5 resets)
        // Padrao: 90s — rapido o suficiente para recuperacao eficaz
        'aggressive_reset_interval_seconds' => (int) env('STREAM_RESET_INTERVAL', 90),

        // Apos quantos hard resets entrar em modo manutencao (intervalo maior)
        'maintenance_after_resets' => (int) env('STREAM_MAINTENANCE_AFTER_RESETS', 5),

        // Intervalo entre hard resets no modo manutencao
        // Para cameras que ficam desligadas por horas (ex: queda de energia)
        'maintenance_interval_seconds' => (int) env('STREAM_MAINTENANCE_INTERVAL', 300),

        // Segundos de not-ready antes de marcar camera como offline no UI
        // Hiccups breves (< 60s) NAO marcam a camera como offline
        'offline_after_seconds' => (int) env('STREAM_OFFLINE_AFTER_SECONDS', 60),
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
