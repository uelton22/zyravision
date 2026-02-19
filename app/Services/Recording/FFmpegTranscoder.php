<?php

namespace App\Services\Recording;

use App\Models\Camera;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * FFmpeg Transcoder Service.
 *
 * Manages FFmpeg processes that read from MediaMTX RTSP relay and write
 * transcoded segments to the same recording directory that MediaMTX would use.
 *
 * Architecture:
 *   Camera → RTSP → MediaMTX → RTSP relay → FFmpeg → /recordings/{path}/*.ts
 *
 * When quality = 'original', MediaMTX records natively (no FFmpeg needed).
 * When quality != 'original', FFmpeg transcodes and writes segments.
 */
class FFmpegTranscoder
{
    public function __construct(
        private FFmpegProcessManager $processManager,
    ) {}

    /**
     * Start an FFmpeg transcoding process for a camera.
     */
    public function start(Camera $camera, string $mediamtxPath): bool
    {
        if ($this->isRunning($camera)) {
            Log::info("FFmpeg transcoder ja esta rodando para camera {$camera->id}.");

            return true;
        }

        $inputUrl = $this->buildInputUrl($mediamtxPath);
        $outputPath = $this->ensureOutputDirectory($mediamtxPath);

        if (! $outputPath) {
            Log::error("Nao foi possivel criar diretorio de saida para camera {$camera->id}.");

            return false;
        }

        $command = $this->buildCommand($camera, $inputUrl, $outputPath);

        Log::info("Iniciando FFmpeg transcoder para camera {$camera->id}.", [
            'quality' => $camera->getEffectiveRecordingQuality(),
            'params' => $camera->getRecordingParams(),
            'input' => $inputUrl,
            'output' => $outputPath,
        ]);

        $pid = $this->processManager->startProcess($camera->id, $command, $mediamtxPath);

        if ($pid) {
            $this->processManager->addToIndex($camera->id);

            return true;
        }

        return false;
    }

    /**
     * Stop the FFmpeg transcoding process for a camera.
     */
    public function stop(Camera $camera): bool
    {
        return $this->processManager->stopProcess($camera->id);
    }

    /**
     * Check if FFmpeg is running for a camera.
     */
    public function isRunning(Camera $camera): bool
    {
        return $this->processManager->isAlive($camera->id);
    }

    /**
     * Get status info for a camera's transcoder.
     */
    public function getStatus(Camera $camera): array
    {
        $info = $this->processManager->getProcessInfo($camera->id);

        if (! $info) {
            return [
                'status' => 'stopped',
                'pid' => null,
                'quality' => $camera->getEffectiveRecordingQuality(),
            ];
        }

        return [
            'status' => 'running',
            'pid' => $info['pid'],
            'started_at' => $info['started_at'],
            'quality' => $camera->getEffectiveRecordingQuality(),
            'params' => $camera->getRecordingParams(),
        ];
    }

    /**
     * Build the RTSP input URL from MediaMTX relay.
     */
    private function buildInputUrl(string $mediamtxPath): string
    {
        $host = config('streaming.mediamtx.host', 'mediamtx');
        $port = config('streaming.mediamtx.rtsp_port', 8554);
        $user = config('streaming.mediamtx.api_user', 'zyravision');
        $pass = config('streaming.mediamtx.api_pass', 'zyravision_api_secret');

        return "rtsp://{$user}:{$pass}@{$host}:{$port}/{$mediamtxPath}";
    }

    /**
     * Ensure the output directory exists.
     */
    private function ensureOutputDirectory(string $mediamtxPath): ?string
    {
        $basePath = config('streaming.recording.base_path', '/recordings');
        $outputDir = "{$basePath}/{$mediamtxPath}";

        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        return File::isDirectory($outputDir) ? $outputDir : null;
    }

    /**
     * Build the complete FFmpeg command array.
     */
    private function buildCommand(Camera $camera, string $inputUrl, string $outputPath): array
    {
        $params = $camera->getRecordingParams();
        $binary = config('transcoding.ffmpeg.binary', 'ffmpeg');
        $segmentDuration = config('transcoding.segment.duration', 60);
        $filenamePattern = config('transcoding.segment.filename_pattern', '%Y-%m-%d_%H-%M-%S.mp4');
        $rtspTransport = config('transcoding.rtsp.transport', 'tcp');

        $cmd = [
            $binary,
            '-hide_banner',
            '-loglevel', 'warning',
            '-rtsp_transport', $rtspTransport,
            '-i', $inputUrl,
        ];

        // Video encoding
        $videoParams = $this->buildVideoParams($params);
        $cmd = array_merge($cmd, $videoParams);

        // Audio encoding
        $audioParams = $this->buildAudioParams($camera);
        $cmd = array_merge($cmd, $audioParams);

        // Segment output (MPEG-TS for seamless HLS playback)
        $cmd = array_merge($cmd, [
            '-f', 'segment',
            '-segment_time', (string) $segmentDuration,
            '-segment_format', 'mpegts',
            '-reset_timestamps', '1',
            '-strftime', '1',
            $outputPath . '/' . $filenamePattern,
        ]);

        return $cmd;
    }

    /**
     * Build video encoding parameters.
     */
    private function buildVideoParams(array $params): array
    {
        $codec = $params['codec'] ?? 'h264';
        $resolution = $params['resolution'] ?? null;
        $fps = $params['fps'] ?? null;
        $bitrate = $params['bitrate'] ?? null;

        // Passthrough (copy)
        if ($codec === 'copy') {
            return ['-c:v', 'copy'];
        }

        $encodingConfig = config("transcoding.encoding.{$codec}", config('transcoding.encoding.h264'));
        $cmd = [];

        // Encoder
        $cmd[] = '-c:v';
        $cmd[] = $encodingConfig['encoder'] ?? 'libx264';

        // Preset
        if (! empty($encodingConfig['preset'])) {
            $cmd[] = '-preset';
            $cmd[] = $encodingConfig['preset'];
        }

        // Tune
        if (! empty($encodingConfig['tune'])) {
            $cmd[] = '-tune';
            $cmd[] = $encodingConfig['tune'];
        }

        // Profile (h264 only)
        if (! empty($encodingConfig['profile'])) {
            $cmd[] = '-profile:v';
            $cmd[] = $encodingConfig['profile'];
        }

        // Resolution (scale filter)
        if ($resolution) {
            $parts = explode('x', $resolution);
            if (count($parts) === 2) {
                $height = $parts[1];
                // -2 ensures width is divisible by 2
                $cmd[] = '-vf';
                $cmd[] = "scale=-2:{$height}";
            }
        }

        // Bitrate
        if ($bitrate) {
            $cmd[] = '-b:v';
            $cmd[] = $bitrate;

            // Max bitrate (125% of target)
            $maxBitrate = $this->calculateMaxBitrate($bitrate);
            if ($maxBitrate) {
                $cmd[] = '-maxrate';
                $cmd[] = $maxBitrate;
                $cmd[] = '-bufsize';
                $cmd[] = $this->calculateBufSize($bitrate);
            }
        }

        // FPS
        if ($fps) {
            $cmd[] = '-r';
            $cmd[] = (string) $fps;
        }

        return $cmd;
    }

    /**
     * Build audio encoding parameters.
     */
    private function buildAudioParams(Camera $camera): array
    {
        if (! $camera->has_audio) {
            return ['-an']; // No audio
        }

        $audioCodec = config('transcoding.audio.codec', 'aac');
        $audioBitrate = config('transcoding.audio.bitrate', '64k');
        $audioChannels = config('transcoding.audio.channels', 1);

        return [
            '-c:a', $audioCodec,
            '-b:a', $audioBitrate,
            '-ac', (string) $audioChannels,
        ];
    }

    /**
     * Calculate max bitrate from target bitrate (125%).
     */
    private function calculateMaxBitrate(string $bitrate): ?string
    {
        $value = (float) $bitrate;
        $unit = str_replace((string) $value, '', $bitrate);

        return ($value * 1.25) . $unit;
    }

    /**
     * Calculate buffer size from target bitrate (2x).
     */
    private function calculateBufSize(string $bitrate): string
    {
        $value = (float) $bitrate;
        $unit = str_replace((string) $value, '', $bitrate);

        return ($value * 2) . $unit;
    }
}
