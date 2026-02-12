<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Job despachado pelo RecordingManager quando uma camera precisa de transcodificacao.
 *
 * Este job NAO executa FFmpeg diretamente. Ele registra o comando no Redis
 * para que um worker transcoder (container dedicado) inicie o processo FFmpeg.
 *
 * Fluxo:
 *   RecordingManager -> dispatch(StartTranscodingJob) -> Redis
 *   TranscoderWorkerCommand (outro container) -> detecta comando -> inicia FFmpeg
 */
class StartTranscodingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public int $tries = 3;

    public function __construct(
        public string $cameraId,
        public string $mediamtxPath,
        public string $quality,
        public array $params,
    ) {
        $this->onQueue('transcoding');
    }

    public function handle(): void
    {
        $commandKey = 'transcoding:commands';
        $cameraKey = "transcoding:cameras:{$this->cameraId}";

        // Register the transcoding request in Redis
        Cache::put($cameraKey, [
            'camera_id' => $this->cameraId,
            'mediamtx_path' => $this->mediamtxPath,
            'quality' => $this->quality,
            'params' => $this->params,
            'status' => 'pending',
            'requested_at' => now()->toIso8601String(),
        ], 86400); // 24h TTL

        // Push command to the transcoding commands list (FIFO queue)
        $command = json_encode([
            'action' => 'start',
            'camera_id' => $this->cameraId,
            'mediamtx_path' => $this->mediamtxPath,
            'quality' => $this->quality,
            'params' => $this->params,
            'timestamp' => now()->toIso8601String(),
        ]);

        \Illuminate\Support\Facades\Redis::rpush($commandKey, $command);

        Log::info("Comando de transcodificacao registrado para camera {$this->cameraId}.", [
            'quality' => $this->quality,
            'mediamtx_path' => $this->mediamtxPath,
        ]);
    }

    public function tags(): array
    {
        return ['transcoding', "camera:{$this->cameraId}"];
    }
}
