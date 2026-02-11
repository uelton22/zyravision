<?php

namespace App\Console\Commands;

use App\Models\Camera;
use App\Services\Streaming\MediaMtxClient;
use App\Services\Streaming\StreamManager;
use Illuminate\Console\Command;

class StartContinuousRecordingCommand extends Command
{
    protected $signature = 'recording:start-continuous
        {--camera= : Camera ID (all if omitted)}';

    protected $description = 'Enable continuous recording for cameras that have it configured';

    public function handle(StreamManager $streamManager, MediaMtxClient $mediaMtx): int
    {
        $query = Camera::where('continuous_recording', true)->where('is_active', true);

        if ($cameraId = $this->option('camera')) {
            $query->where('id', $cameraId);
        }

        $cameras = $query->with('activeStream')->get();

        if ($cameras->isEmpty()) {
            $this->warn('Nenhuma camera com gravacao continua encontrada.');

            return self::SUCCESS;
        }

        $this->info("Habilitando gravacao continua para {$cameras->count()} camera(s)...");

        foreach ($cameras as $camera) {
            try {
                $stream = $camera->activeStream;

                if (! $stream) {
                    $this->line("  [{$camera->name}] Stream inativo, iniciando...");
                    $stream = $streamManager->startStream($camera);

                    if ($stream->status !== 'active') {
                        $this->warn("  [{$camera->name}] Falha ao iniciar stream: {$stream->status}");

                        continue;
                    }
                }

                // Enable recording on MediaMTX
                $result = $mediaMtx->editPath($stream->mediamtx_path, ['record' => true]);

                if ($result) {
                    $camera->update(['is_recording' => true]);
                    $this->info("  [{$camera->name}] Gravacao continua habilitada.");
                } else {
                    $this->warn("  [{$camera->name}] Falha ao habilitar gravacao no MediaMTX.");
                }
            } catch (\Throwable $e) {
                $this->error("  [{$camera->name}] Erro: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
