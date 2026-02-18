<?php

namespace App\Console\Commands;

use App\Models\Camera;
use App\Models\RecordingSegment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * TranscoderWorkerCommand — Daemon de longa duracao para transcodificacao FFmpeg.
 *
 * Este comando roda em um container dedicado (docker/transcoder/Dockerfile)
 * e gerencia processos FFmpeg filhos. Ele escuta uma fila Redis para
 * receber comandos de start/stop de transcodificacao.
 *
 * Arquitetura:
 *   Laravel (app container) -> dispatch(StartTranscodingJob) -> Redis
 *   TranscoderWorkerCommand (transcoder container) -> lê Redis -> inicia FFmpeg
 *
 * Cada instancia deste daemon pode gerenciar ate N cameras simultaneas
 * (configurável via FFMPEG_MAX_CONCURRENT).
 *
 * Escalabilidade: Rode multiplas replicas via docker-compose deploy.replicas
 */
class TranscoderWorkerCommand extends Command
{
    protected $signature = 'transcoder:worker
        {--max-cameras=10 : Maximo de cameras simultaneas por worker}
        {--poll-interval=2 : Intervalo em segundos entre verificacoes da fila}';

    protected $description = 'Daemon do worker de transcodificacao FFmpeg (roda em container dedicado)';

    private string $workerId;

    /** @var array<string, array{process: resource, pipes: array, camera_id: string, path: string}> */
    private array $activeProcesses = [];

    private bool $shouldRun = true;

    private string $commandsKey = 'transcoding:commands';

    private string $ffmpegTasksKey = 'transcoding:ffmpeg_tasks';

    public function handle(): int
    {
        $this->workerId = gethostname() . '_' . getmypid();
        $maxCameras = (int) $this->option('max-cameras');
        $pollInterval = (int) $this->option('poll-interval');

        $this->info("=== ZyraVision Transcoder Worker ===");
        $this->info("Worker ID: {$this->workerId}");
        $this->info("Max cameras: {$maxCameras}");
        $this->info("Poll interval: {$pollInterval}s");
        $this->info("FFmpeg binary: " . $this->findFfmpeg());
        $this->newLine();

        // Register signal handlers for graceful shutdown
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => $this->gracefulShutdown());
        pcntl_signal(SIGINT, fn () => $this->gracefulShutdown());

        // Register this worker in Redis
        $this->registerWorker();

        $this->info("Worker pronto. Aguardando comandos de transcodificacao...");

        while ($this->shouldRun) {
            // 1. Process pending transcoding commands from Redis queue
            $this->processCommands($maxCameras);

            // 2. Process pending FFmpeg tasks (thumbnails, snapshots)
            $this->processFfmpegTasks();

            // 3. Check health of active FFmpeg processes
            $this->checkProcessHealth();

            // 4. Update worker status in Redis
            $this->updateWorkerStatus();

            // 5. Sleep before next poll
            sleep($pollInterval);
        }

        $this->info("Worker encerrado.");

        return self::SUCCESS;
    }

    /**
     * Process pending transcoding commands from Redis.
     */
    private function processCommands(int $maxCameras): void
    {
        while (true) {
            $raw = Redis::lpop($this->commandsKey);
            if (! $raw) {
                break;
            }

            $command = json_decode($raw, true);
            if (! $command || ! isset($command['action'])) {
                Log::warning('Comando de transcodificacao invalido recebido.', ['raw' => $raw]);
                continue;
            }

            $action = $command['action'];
            $cameraId = $command['camera_id'] ?? null;

            if (! $cameraId) {
                continue;
            }

            switch ($action) {
                case 'start':
                    if (count($this->activeProcesses) >= $maxCameras) {
                        // Worker esta cheio, re-enqueue para outro worker pegar
                        Redis::rpush($this->commandsKey, $raw);
                        Log::info("Worker cheio ({$maxCameras} cameras). Comando re-enfileirado.", [
                            'camera_id' => $cameraId,
                        ]);
                        return; // Stop processing commands this cycle
                    }

                    if (isset($this->activeProcesses[$cameraId])) {
                        Log::info("FFmpeg ja esta rodando para camera {$cameraId} neste worker.");
                        break;
                    }

                    $this->startFfmpeg($cameraId, $command);
                    break;

                case 'stop':
                    $this->stopFfmpeg($cameraId);
                    break;

                default:
                    Log::warning("Acao de transcodificacao desconhecida: {$action}");
            }
        }
    }

    /**
     * Start an FFmpeg process for a camera.
     */
    private function startFfmpeg(string $cameraId, array $command): void
    {
        $mediamtxPath = $command['mediamtx_path'] ?? null;
        $quality = $command['quality'] ?? 'medium';
        $params = $command['params'] ?? [];

        if (! $mediamtxPath) {
            Log::error("MediaMTX path ausente para camera {$cameraId}.");
            return;
        }

        $inputUrl = $this->buildInputUrl($mediamtxPath);
        $outputDir = $this->ensureOutputDirectory($mediamtxPath);

        if (! $outputDir) {
            Log::error("Nao foi possivel criar diretorio de saida para camera {$cameraId}.");
            return;
        }

        $ffmpegCmd = $this->buildFfmpegCommand($params, $inputUrl, $outputDir);
        $cmdString = implode(' ', $ffmpegCmd);

        $this->info("Iniciando FFmpeg para camera {$cameraId} (quality: {$quality})");

        $descriptors = [
            0 => ['pipe', 'r'],       // stdin
            1 => ['pipe', 'w'],       // stdout
            2 => ['pipe', 'w'],       // stderr
        ];

        $process = proc_open($cmdString, $descriptors, $pipes);

        if (! is_resource($process)) {
            Log::error("Falha ao iniciar FFmpeg para camera {$cameraId}.");
            $this->updateCameraStatus($cameraId, 'error', 'Falha ao iniciar processo FFmpeg');
            return;
        }

        // Make stderr non-blocking for log reading
        stream_set_blocking($pipes[2], false);

        $status = proc_get_status($process);

        $this->activeProcesses[$cameraId] = [
            'process' => $process,
            'pipes' => $pipes,
            'camera_id' => $cameraId,
            'path' => $mediamtxPath,
            'quality' => $quality,
            'pid' => $status['pid'],
            'started_at' => now()->toIso8601String(),
        ];

        $this->updateCameraStatus($cameraId, 'running', null, $status['pid']);

        Log::info("FFmpeg iniciado para camera {$cameraId}.", [
            'pid' => $status['pid'],
            'quality' => $quality,
            'worker_id' => $this->workerId,
        ]);
    }

    /**
     * Stop an FFmpeg process for a camera.
     */
    private function stopFfmpeg(string $cameraId): void
    {
        if (! isset($this->activeProcesses[$cameraId])) {
            // Pode estar em outro worker, limpar status apenas
            $this->updateCameraStatus($cameraId, 'stopped');
            return;
        }

        $entry = $this->activeProcesses[$cameraId];
        $process = $entry['process'];
        $pipes = $entry['pipes'];

        $this->info("Parando FFmpeg para camera {$cameraId} (PID: {$entry['pid']})");

        // Close stdin to signal FFmpeg to finish
        @fclose($pipes[0]);

        // Send SIGTERM for graceful shutdown
        $status = proc_get_status($process);
        if ($status['running']) {
            posix_kill($status['pid'], SIGTERM);

            // Wait up to 5 seconds for graceful shutdown
            $waited = 0;
            while ($waited < 5) {
                usleep(500000); // 0.5s
                $waited += 0.5;
                $status = proc_get_status($process);
                if (! $status['running']) {
                    break;
                }
            }

            // Force kill if still running
            if ($status['running']) {
                posix_kill($status['pid'], SIGKILL);
                usleep(500000);
                $this->warn("FFmpeg PID {$entry['pid']} forcado (SIGKILL) para camera {$cameraId}.");
            }
        }

        @fclose($pipes[1]);
        @fclose($pipes[2]);
        proc_close($process);

        unset($this->activeProcesses[$cameraId]);
        $this->updateCameraStatus($cameraId, 'stopped');

        Log::info("FFmpeg parado para camera {$cameraId}.");
    }

    /**
     * Check health of all active FFmpeg processes.
     */
    private function checkProcessHealth(): void
    {
        foreach ($this->activeProcesses as $cameraId => $entry) {
            $status = proc_get_status($entry['process']);

            if (! $status['running']) {
                $exitCode = $status['exitcode'];
                $this->warn("FFmpeg para camera {$cameraId} encerrou (exit: {$exitCode}).");

                // Read last stderr output for diagnostics
                $stderr = @stream_get_contents($entry['pipes'][2]);
                if ($stderr) {
                    Log::warning("FFmpeg stderr para camera {$cameraId}: " . substr($stderr, -500));
                }

                @fclose($entry['pipes'][0]);
                @fclose($entry['pipes'][1]);
                @fclose($entry['pipes'][2]);
                proc_close($entry['process']);

                unset($this->activeProcesses[$cameraId]);

                $this->updateCameraStatus($cameraId, 'error', "FFmpeg encerrou com exit code {$exitCode}");

                Log::error("FFmpeg encerrou inesperadamente para camera {$cameraId}.", [
                    'exit_code' => $exitCode,
                    'worker_id' => $this->workerId,
                ]);
            }
        }
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
     * Ensure output directory exists.
     */
    private function ensureOutputDirectory(string $mediamtxPath): ?string
    {
        $basePath = config('streaming.recording.base_path', '/recordings');
        $outputDir = "{$basePath}/{$mediamtxPath}";

        if (! is_dir($outputDir)) {
            @mkdir($outputDir, 0755, true);
        }

        return is_dir($outputDir) ? $outputDir : null;
    }

    /**
     * Build the FFmpeg command.
     */
    private function buildFfmpegCommand(array $params, string $inputUrl, string $outputPath): array
    {
        $binary = $this->findFfmpeg();
        $segmentDuration = config('transcoding.segment.duration', 60);
        $filenamePattern = config('transcoding.segment.filename_pattern', '%Y-%m-%d_%H-%M-%S.mp4');
        $rtspTransport = config('transcoding.rtsp.transport', 'tcp');
        $logLevel = env('FFMPEG_LOG_LEVEL', 'warning');

        $cmd = [
            $binary,
            '-hide_banner',
            '-loglevel', $logLevel,
            '-rtsp_transport', $rtspTransport,
            '-i', $inputUrl,
        ];

        // Video encoding
        $codec = $params['codec'] ?? 'h264';
        if ($codec === 'copy') {
            $cmd[] = '-c:v';
            $cmd[] = 'copy';
        } else {
            $encodingConfig = config("transcoding.encoding.{$codec}", config('transcoding.encoding.h264'));

            $cmd[] = '-c:v';
            $cmd[] = $encodingConfig['encoder'] ?? 'libx264';

            if (! empty($encodingConfig['preset'])) {
                $cmd[] = '-preset';
                $cmd[] = $encodingConfig['preset'];
            }

            if (! empty($encodingConfig['tune'])) {
                $cmd[] = '-tune';
                $cmd[] = $encodingConfig['tune'];
            }

            if (! empty($encodingConfig['profile'])) {
                $cmd[] = '-profile:v';
                $cmd[] = $encodingConfig['profile'];
            }

            $resolution = $params['resolution'] ?? null;
            if ($resolution) {
                $parts = explode('x', $resolution);
                if (count($parts) === 2) {
                    $cmd[] = '-vf';
                    $cmd[] = "scale=-2:{$parts[1]}";
                }
            }

            $bitrate = $params['bitrate'] ?? null;
            if ($bitrate) {
                $cmd[] = '-b:v';
                $cmd[] = $bitrate;

                $value = (float) $bitrate;
                $unit = str_replace((string) $value, '', $bitrate);
                $cmd[] = '-maxrate';
                $cmd[] = ($value * 1.25) . $unit;
                $cmd[] = '-bufsize';
                $cmd[] = ($value * 2) . $unit;
            }

            $fps = $params['fps'] ?? null;
            if ($fps) {
                $cmd[] = '-r';
                $cmd[] = (string) $fps;
            }
        }

        // Audio: disable for now
        $cmd[] = '-an';

        // Segment output
        $cmd = array_merge($cmd, [
            '-f', 'segment',
            '-segment_time', (string) $segmentDuration,
            '-segment_format', 'mp4',
            '-reset_timestamps', '1',
            '-strftime', '1',
            '-movflags', '+faststart',
            $outputPath . '/' . $filenamePattern,
        ]);

        return $cmd;
    }

    /**
     * Find FFmpeg binary path.
     */
    private function findFfmpeg(): string
    {
        return config('transcoding.ffmpeg.binary', 'ffmpeg');
    }

    /**
     * Update camera transcoding status in Redis.
     */
    private function updateCameraStatus(string $cameraId, string $status, ?string $error = null, ?int $pid = null): void
    {
        $key = "transcoding:cameras:{$cameraId}";
        $data = Cache::get($key, []);

        $data['status'] = $status;
        $data['worker_id'] = $this->workerId;
        $data['updated_at'] = now()->toIso8601String();

        if ($pid !== null) {
            $data['pid'] = $pid;
        }

        if ($error !== null) {
            $data['error'] = $error;
        }

        if ($status === 'stopped') {
            $data['stopped_at'] = now()->toIso8601String();
        }

        Cache::put($key, $data, 86400);
    }

    /**
     * Register this worker in Redis.
     */
    private function registerWorker(): void
    {
        $key = "transcoding:workers:{$this->workerId}";
        Cache::put($key, [
            'worker_id' => $this->workerId,
            'hostname' => gethostname(),
            'pid' => getmypid(),
            'started_at' => now()->toIso8601String(),
            'cameras' => [],
            'max_cameras' => (int) $this->option('max-cameras'),
        ], 86400);
    }

    /**
     * Update worker status in Redis.
     */
    private function updateWorkerStatus(): void
    {
        $key = "transcoding:workers:{$this->workerId}";
        $cameras = array_keys($this->activeProcesses);

        Cache::put($key, [
            'worker_id' => $this->workerId,
            'hostname' => gethostname(),
            'pid' => getmypid(),
            'started_at' => Cache::get($key)['started_at'] ?? now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'cameras' => $cameras,
            'active_count' => count($cameras),
            'max_cameras' => (int) $this->option('max-cameras'),
        ], 86400);
    }

    // ─── FFmpeg Tasks (thumbnails, snapshots) ──────────────

    /**
     * Process lightweight FFmpeg tasks: thumbnail generation, snapshot capture.
     * These are short-lived operations (1-3s) unlike continuous transcoding.
     * Max 5 tasks per poll cycle to avoid blocking the main loop.
     */
    private function processFfmpegTasks(): void
    {
        $maxPerCycle = 5;
        $processed = 0;

        while ($processed < $maxPerCycle) {
            $raw = Redis::lpop($this->ffmpegTasksKey);
            if (! $raw) {
                break;
            }

            $task = json_decode($raw, true);
            if (! $task || ! isset($task['action'])) {
                Log::warning('FFmpeg task invalida recebida.', ['raw' => $raw]);
                continue;
            }

            try {
                match ($task['action']) {
                    'generate_thumbnail' => $this->handleGenerateThumbnail($task),
                    'capture_snapshot' => $this->handleCaptureSnapshot($task),
                    default => Log::warning("FFmpeg task desconhecida: {$task['action']}"),
                };
            } catch (\Throwable $e) {
                Log::error("Erro ao processar FFmpeg task: {$e->getMessage()}", [
                    'action' => $task['action'],
                    'error' => $e->getMessage(),
                ]);
            }

            $processed++;
        }
    }

    /**
     * Generate a thumbnail from a recording segment video file.
     */
    private function handleGenerateThumbnail(array $task): void
    {
        $segmentId = $task['segment_id'] ?? null;
        if (! $segmentId) {
            return;
        }

        $segment = RecordingSegment::find($segmentId);
        if (! $segment) {
            return;
        }

        if ($segment->thumbnail_path) {
            $fullPath = storage_path('app/public' . str_replace('/storage', '', $segment->thumbnail_path));
            if (File::exists($fullPath)) {
                return;
            }
        }

        $videoPath = $segment->local_path;
        if (! $videoPath || ! File::exists($videoPath)) {
            Log::debug("Thumbnail: video nao encontrado para segmento {$segmentId}.");
            return;
        }

        $thumbnailDir = storage_path("app/public/thumbnails/{$segment->camera_id}");
        if (! File::isDirectory($thumbnailDir)) {
            File::makeDirectory($thumbnailDir, 0755, true);
        }

        $thumbnailFilename = pathinfo($segment->filename ?? $segment->id, PATHINFO_FILENAME) . '.jpg';
        $thumbnailPath = "{$thumbnailDir}/{$thumbnailFilename}";

        $ffmpeg = $this->findFfmpeg();
        $seekTime = min(1, max(0, ($segment->duration_seconds ?? 0) - 1));

        $cmd = implode(' ', array_map('escapeshellarg', [
            $ffmpeg, '-y',
            '-ss', (string) $seekTime,
            '-i', $videoPath,
            '-frames:v', '1',
            '-vf', 'scale=320:-1',
            '-q:v', '5',
            $thumbnailPath,
        ]));

        exec($cmd . ' 2>&1', $output, $exitCode);

        if ($exitCode === 0 && File::exists($thumbnailPath)) {
            $publicPath = "/storage/thumbnails/{$segment->camera_id}/{$thumbnailFilename}";
            $segment->update(['thumbnail_path' => $publicPath]);

            if ($this->output->isVerbose()) {
                $this->line("  [thumb] Segmento {$segmentId} -> {$publicPath}");
            }
        } else {
            Log::warning("Thumbnail falhou para segmento {$segmentId}.", [
                'exit_code' => $exitCode,
                'output' => implode("\n", array_slice($output, -5)),
            ]);
        }
    }

    /**
     * Capture a snapshot from a camera's live stream via MediaMTX relay.
     */
    private function handleCaptureSnapshot(array $task): void
    {
        $cameraId = $task['camera_id'] ?? null;
        $mediamtxPath = $task['mediamtx_path'] ?? null;

        if (! $cameraId || ! $mediamtxPath) {
            return;
        }

        $camera = Camera::find($cameraId);
        if (! $camera || ! $camera->is_active) {
            return;
        }

        $filename = "snapshots/{$cameraId}.jpg";
        $storagePath = storage_path("app/public/{$filename}");

        $dir = dirname($storagePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $quality = $task['quality'] ?? config('streaming.snapshots.quality', 80);
        $rtspUrl = $this->buildInputUrl($mediamtxPath);
        $ffmpeg = $this->findFfmpeg();

        $cmd = implode(' ', array_map('escapeshellarg', [
            $ffmpeg, '-y',
            '-rtsp_transport', 'tcp',
            '-i', $rtspUrl,
            '-frames:v', '1',
            '-q:v', (string) round((100 - $quality) * 31 / 100),
            $storagePath,
        ]));

        exec($cmd . ' 2>&1', $output, $exitCode);

        if ($exitCode === 0 && file_exists($storagePath)) {
            $camera->update(['snapshot_url' => "/storage/{$filename}"]);

            if ($this->output->isVerbose()) {
                $this->line("  [snap] Camera {$cameraId} -> /storage/{$filename}");
            }
        } else {
            Log::warning("Snapshot falhou para camera {$cameraId}.", [
                'mediamtx_path' => $mediamtxPath,
                'exit_code' => $exitCode,
                'output' => implode("\n", array_slice($output, -3)),
            ]);
        }
    }

    // ─── Lifecycle ───────────────────────────────────────────

    /**
     * Graceful shutdown: stop all FFmpeg processes and deregister.
     */
    private function gracefulShutdown(): void
    {
        $this->info("\nRecebido sinal de shutdown. Parando processos FFmpeg...");
        $this->shouldRun = false;

        foreach (array_keys($this->activeProcesses) as $cameraId) {
            $this->stopFfmpeg($cameraId);
        }

        // Deregister worker from Redis
        Cache::forget("transcoding:workers:{$this->workerId}");

        $this->info("Todos os processos FFmpeg foram parados. Worker desregistrado.");
    }
}
