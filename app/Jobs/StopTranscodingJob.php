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
 * Job despachado pelo RecordingManager quando uma camera deve parar transcodificacao.
 *
 * Registra o comando de parada no Redis para que o worker transcoder
 * (container dedicado) termine o processo FFmpeg correspondente.
 */
class StopTranscodingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public int $tries = 3;

    public function __construct(
        public string $cameraId,
    ) {
        $this->onQueue('transcoding');
    }

    public function handle(): void
    {
        $commandKey = 'transcoding:commands';

        // Push stop command to the transcoding commands list
        $command = json_encode([
            'action' => 'stop',
            'camera_id' => $this->cameraId,
            'timestamp' => now()->toIso8601String(),
        ]);

        \Illuminate\Support\Facades\Redis::rpush($commandKey, $command);

        // Update camera status in Redis
        $cameraKey = "transcoding:cameras:{$this->cameraId}";
        $data = Cache::get($cameraKey);

        if ($data) {
            $data['status'] = 'stopping';
            $data['stop_requested_at'] = now()->toIso8601String();
            Cache::put($cameraKey, $data, 86400);
        }

        Log::info("Comando de parada de transcodificacao registrado para camera {$this->cameraId}.");
    }

    public function tags(): array
    {
        return ['transcoding', "camera:{$this->cameraId}"];
    }
}
