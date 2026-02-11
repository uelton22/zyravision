<?php

namespace App\Console\Commands;

use App\Models\RecordingSegment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SegmentsClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'segments:clear
                            {--cache-only : Only clear cache, keep database records}
                            {--db-only : Only clear database, keep cache files}
                            {--camera= : Only clear for specific camera ID}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear recording segment cache and/or database records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cacheOnly = $this->option('cache-only');
        $dbOnly = $this->option('db-only');
        $cameraId = $this->option('camera');
        $force = $this->option('force');

        // Validation
        if ($cacheOnly && $dbOnly) {
            $this->error('Cannot use --cache-only and --db-only together.');
            return self::FAILURE;
        }

        // Summary
        $this->info('=== Limpeza de Segmentos de Gravacao ===');
        $this->newLine();

        if ($cameraId) {
            $this->info("Camera ID: {$cameraId}");
        } else {
            $this->warn('Todas as cameras serao afetadas!');
        }

        if (!$cacheOnly && !$dbOnly) {
            $this->info('Acao: Limpar CACHE + BANCO DE DADOS');
        } elseif ($cacheOnly) {
            $this->info('Acao: Limpar apenas CACHE (arquivos no disco)');
        } else {
            $this->info('Acao: Limpar apenas BANCO DE DADOS (registros)');
        }

        $this->newLine();

        // Stats before
        $segmentCount = RecordingSegment::query()
            ->when($cameraId, fn($q) => $q->where('camera_id', $cameraId))
            ->count();

        $totalSize = RecordingSegment::query()
            ->when($cameraId, fn($q) => $q->where('camera_id', $cameraId))
            ->sum('file_size_bytes');

        $this->info("Segmentos no banco: {$segmentCount}");
        $this->info('Tamanho total: ' . $this->formatBytes($totalSize));

        // Confirmation
        if (!$force) {
            if (!$this->confirm('Deseja continuar?', false)) {
                $this->info('Operacao cancelada.');
                return self::SUCCESS;
            }
        }

        $this->newLine();

        // 1. Clear cache
        if (!$dbOnly) {
            $this->info('[1/2] Limpando cache de segmentos...');
            $cacheBasePath = storage_path('app/segment_cache');

            if ($cameraId) {
                $cachePath = $cacheBasePath . '/' . $cameraId;
                if (File::isDirectory($cachePath)) {
                    $deleted = File::deleteDirectory($cachePath);
                    if ($deleted) {
                        $this->info("  ✓ Cache da camera {$cameraId} removido.");
                    } else {
                        $this->error("  ✗ Erro ao remover cache da camera {$cameraId}.");
                    }
                } else {
                    $this->info("  ℹ Nenhum cache encontrado para camera {$cameraId}.");
                }
            } else {
                if (File::isDirectory($cacheBasePath)) {
                    $deleted = File::deleteDirectory($cacheBasePath);
                    File::makeDirectory($cacheBasePath, 0755, true);
                    if ($deleted) {
                        $this->info('  ✓ Todo o cache de segmentos removido.');
                    } else {
                        $this->error('  ✗ Erro ao remover cache.');
                    }
                } else {
                    $this->info('  ℹ Nenhum cache encontrado.');
                }
            }
        }

        // 2. Clear database
        if (!$cacheOnly) {
            $this->info('[2/2] Limpando registros no banco de dados...');

            $query = RecordingSegment::query();

            if ($cameraId) {
                $query->where('camera_id', $cameraId);
            }

            $deletedCount = $query->delete();

            if ($deletedCount > 0) {
                $this->info("  ✓ {$deletedCount} segmentos removidos do banco.");
            } else {
                $this->info('  ℹ Nenhum segmento encontrado no banco.');
            }
        }

        $this->newLine();
        $this->info('✓ Limpeza concluida com sucesso!');

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } else {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
    }
}
