<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FFmpeg Configuration
    |--------------------------------------------------------------------------
    */

    'ffmpeg' => [
        'binary' => env('FFMPEG_BINARY', 'ffmpeg'),
        'ffprobe_binary' => env('FFPROBE_BINARY', 'ffprobe'),
        'timeout' => (int) env('FFMPEG_TIMEOUT', 0), // 0 = no timeout (long-running)
        'stop_timeout' => 5, // seconds to wait after SIGTERM before SIGKILL
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Presets
    |--------------------------------------------------------------------------
    | Each preset defines resolution, codec, fps, and bitrate for transcoding.
    | 'original' uses MediaMTX native recording (passthrough, no FFmpeg).
    */

    'presets' => [
        'original' => [
            'label' => 'Original (passthrough)',
            'resolution' => null,
            'codec' => 'copy',
            'fps' => null,
            'bitrate' => null,
            'max_bitrate' => null,
        ],
        'high' => [
            'label' => 'Alta (1080p)',
            'resolution' => '1920x1080',
            'codec' => 'h264',
            'fps' => 30,
            'bitrate' => '4M',
            'max_bitrate' => '5M',
        ],
        'medium' => [
            'label' => 'Media (720p)',
            'resolution' => '1280x720',
            'codec' => 'h264',
            'fps' => 15,
            'bitrate' => '2M',
            'max_bitrate' => '2.5M',
        ],
        'low' => [
            'label' => 'Baixa (480p)',
            'resolution' => '640x480',
            'codec' => 'h264',
            'fps' => 10,
            'bitrate' => '1M',
            'max_bitrate' => '1.5M',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | FFmpeg Encoding Options
    |--------------------------------------------------------------------------
    */

    'encoding' => [
        'h264' => [
            'encoder' => 'libx264',
            'preset' => env('FFMPEG_H264_PRESET', 'fast'),
            'tune' => 'zerolatency',
            'profile' => 'main',
        ],
        'h265' => [
            'encoder' => 'libx265',
            'preset' => env('FFMPEG_H265_PRESET', 'fast'),
            'tune' => 'zerolatency',
            'profile' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Settings
    |--------------------------------------------------------------------------
    */

    'audio' => [
        'codec' => 'aac',
        'bitrate' => '64k',
        'channels' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Segment Output
    |--------------------------------------------------------------------------
    | Must match MediaMTX segment settings for SegmentWatcherService compatibility.
    */

    'segment' => [
        'duration' => 60, // seconds (matches mediamtx recordSegmentDuration: 1m)
        'format' => 'mpegts',
        'filename_pattern' => '%Y-%m-%d_%H-%M-%S.ts',
    ],

    /*
    |--------------------------------------------------------------------------
    | RTSP Input
    |--------------------------------------------------------------------------
    */

    'rtsp' => [
        'transport' => 'tcp',
        'stimeout' => '5000000', // connection timeout in microseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Process Management
    |--------------------------------------------------------------------------
    */

    'process' => [
        'cache_prefix' => 'ffmpeg_transcoder:',
        'cache_ttl' => 86400, // 24 hours (cleaned up by scheduler)
        'max_concurrent' => (int) env('MAX_TRANSCODING_PROCESSES', 20),
    ],

];
