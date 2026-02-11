<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Support\Facades\Http;

/**
 * CDN TV Storage Driver.
 * Integrates with proprietary CDN TV APIs for video storage.
 */
class CdntvDriver extends AbstractDriver
{
    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_CDNTV;
    }

    public function connect(): bool
    {
        try {
            $response = $this->apiRequest('GET', '/status');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function store(string $path, string $contents): bool
    {
        try {
            $response = $this->apiRequest('POST', '/files', [
                'path' => $this->buildPath($path),
                'content' => base64_encode($contents),
            ]);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function storeFile(string $path, string $localFilePath): bool
    {
        try {
            $response = Http::timeout(120)
                ->withHeaders($this->getHeaders())
                ->attach('file', file_get_contents($localFilePath), basename($localFilePath))
                ->post($this->getApiUrl('/files/upload'), [
                    'path' => $this->buildPath($path),
                ]);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function retrieve(string $path): string
    {
        $response = $this->apiRequest('GET', '/files/' . urlencode($this->buildPath($path)));

        if (! $response->successful()) {
            throw new \RuntimeException("Arquivo nao encontrado no CDN TV: {$path}");
        }

        return base64_decode($response->json('content', ''));
    }

    public function delete(string $path): bool
    {
        try {
            $response = $this->apiRequest('DELETE', '/files/' . urlencode($this->buildPath($path)));

            return $response->successful();
        } catch (\Throwable) {
            return true;
        }
    }

    public function deleteDirectory(string $directory): bool
    {
        try {
            $response = $this->apiRequest('DELETE', '/directories/' . urlencode($this->buildPath($directory)));

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            $response = $this->apiRequest('HEAD', '/files/' . urlencode($this->buildPath($path)));

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function listFiles(string $directory): array
    {
        try {
            $response = $this->apiRequest('GET', '/directories/' . urlencode($this->buildPath($directory)));

            return $response->json('files', []);
        } catch (\Throwable) {
            return [];
        }
    }

    public function getCapacity(): array
    {
        try {
            $response = $this->apiRequest('GET', '/quota');
            if ($response->successful()) {
                return [
                    'total_gb' => $response->json('total_gb'),
                    'used_gb' => $response->json('used_gb'),
                    'free_gb' => $response->json('free_gb'),
                ];
            }
        } catch (\Throwable) {
            // Ignore
        }

        return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
    }

    public function healthCheck(): array
    {
        $measurement = $this->measureLatency(function () {
            $response = $this->apiRequest('GET', '/health');
            if (! $response->successful()) {
                throw new \RuntimeException('CDN TV API retornou erro: ' . $response->status());
            }

            return true;
        });

        if ($measurement['result'] === false) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => $measurement['latency_ms'],
                'message' => $measurement['error'] ?? 'Falha na conexao com CDN TV',
            ];
        }

        return [
            'status' => $measurement['latency_ms'] > 5000 ? StorageBackend::HEALTH_DEGRADED : StorageBackend::HEALTH_HEALTHY,
            'latency_ms' => $measurement['latency_ms'],
            'message' => 'CDN TV operando normalmente',
        ];
    }

    private function getApiUrl(string $path): string
    {
        $endpoint = rtrim($this->getConfig('api_endpoint'), '/');

        return $endpoint . '/' . ltrim($path, '/');
    }

    private function getHeaders(): array
    {
        $headers = ['Accept' => 'application/json'];

        if ($apiKey = $this->getConfig('api_key')) {
            $headers['X-API-Key'] = $apiKey;
        }

        if ($username = $this->getConfig('username')) {
            $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $this->getConfig('password', ''));
        }

        return $headers;
    }

    private function apiRequest(string $method, string $path, array $data = [])
    {
        $url = $this->getApiUrl($path);
        $request = Http::timeout(30)->withHeaders($this->getHeaders());

        return match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PUT' => $request->put($url, $data),
            'DELETE' => $request->delete($url, $data),
            'HEAD' => $request->head($url),
            default => $request->get($url),
        };
    }

    private function buildPath(string $path): string
    {
        $baseDir = trim($this->getConfig('base_directory', ''), '/');

        return $baseDir ? $baseDir . '/' . ltrim($path, '/') : $path;
    }
}
