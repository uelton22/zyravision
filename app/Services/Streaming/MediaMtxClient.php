<?php

namespace App\Services\Streaming;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MediaMtxClient
{
    private string $baseUrl;
    private int $timeout;
    private string $apiUser;
    private string $apiPass;

    public function __construct()
    {
        $this->baseUrl = config('streaming.mediamtx.api_url', 'http://mediamtx:9997');
        $this->timeout = config('streaming.mediamtx.timeout', 10);
        $this->apiUser = config('streaming.mediamtx.api_user', 'zyravision');
        $this->apiPass = config('streaming.mediamtx.api_pass', 'zyravision_api_secret');
    }

    /**
     * Add a new path (stream source) to MediaMTX.
     */
    public function addPath(string $name, array $config): bool
    {
        try {
            $response = $this->client()->post("/v3/config/paths/add/{$name}", $config);

            if ($response->successful()) {
                Log::info("MediaMTX: path '{$name}' added successfully.");
                return true;
            }

            // Path may already exist — try edit instead
            if ($response->status() === 400) {
                return $this->editPath($name, $config);
            }

            Log::warning("MediaMTX: failed to add path '{$name}'.", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (ConnectionException $e) {
            Log::error("MediaMTX: connection failed when adding path '{$name}'.", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Edit an existing path configuration.
     * Uses /v3/config/paths/patch/{name} (MediaMTX v1.x API).
     */
    public function editPath(string $name, array $config): bool
    {
        try {
            $response = $this->client()->patch("/v3/config/paths/patch/{$name}", $config);

            if ($response->successful()) {
                Log::info("MediaMTX: path '{$name}' edited successfully.", [
                    'config' => array_keys($config),
                ]);
                return true;
            }

            Log::warning("MediaMTX: failed to edit path '{$name}'.", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (ConnectionException $e) {
            Log::error("MediaMTX: connection failed when editing path '{$name}'.", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove a path from MediaMTX.
     */
    public function removePath(string $name): bool
    {
        try {
            $response = $this->client()->delete("/v3/config/paths/delete/{$name}");

            if ($response->successful() || $response->status() === 404) {
                Log::info("MediaMTX: path '{$name}' removed.");
                return true;
            }

            Log::warning("MediaMTX: failed to remove path '{$name}'.", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (ConnectionException $e) {
            Log::error("MediaMTX: connection failed when removing path '{$name}'.", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get info about a specific path (source type, readers, ready state).
     */
    public function getPath(string $name): ?array
    {
        try {
            $response = $this->client()->get("/v3/paths/get/{$name}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (ConnectionException $e) {
            Log::error("MediaMTX: connection failed when getting path '{$name}'.", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * List all paths currently active in MediaMTX.
     */
    public function listPaths(): array
    {
        try {
            $response = $this->client()->get('/v3/paths/list');

            if ($response->successful()) {
                return $response->json('items') ?? [];
            }

            return [];
        } catch (ConnectionException $e) {
            Log::error('MediaMTX: connection failed when listing paths.', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Check if a path is ready and has active readers/source.
     */
    public function isPathReady(string $name): bool
    {
        $path = $this->getPath($name);

        if (! $path) {
            return false;
        }

        return ($path['ready'] ?? false) === true;
    }

    /**
     * Get metrics for a specific path (bytesReceived, readers count, etc.).
     */
    public function getPathMetrics(string $name): array
    {
        $path = $this->getPath($name);

        if (! $path) {
            return [];
        }

        return [
            'ready' => $path['ready'] ?? false,
            'readers' => count($path['readers'] ?? []),
            'bytesReceived' => $path['bytesReceived'] ?? 0,
            'bytesSent' => $path['bytesSent'] ?? 0,
            'source' => $path['source'] ?? null,
        ];
    }

    /**
     * List available recordings for a path within a time range.
     */
    public function listRecordings(string $path, ?string $start = null, ?string $end = null): array
    {
        try {
            $query = ['path' => $path];
            if ($start) {
                $query['start'] = $start;
            }
            if ($end) {
                $query['end'] = $end;
            }

            $response = $this->client()->get('/v3/recordings/list', $query);

            if ($response->successful()) {
                return $response->json('items') ?? [];
            }

            return [];
        } catch (ConnectionException $e) {
            Log::error("MediaMTX: connection failed when listing recordings for '{$path}'.", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get HLS playback URL for a specific recording time range.
     */
    public function getPlaybackUrl(string $path, string $start, ?string $end = null): string
    {
        $url = "/playback/{$path}/index.m3u8?start=" . urlencode($start);
        if ($end) {
            $url .= '&end=' . urlencode($end);
        }

        return $url;
    }

    /**
     * Check if MediaMTX is reachable.
     */
    public function isHealthy(): bool
    {
        try {
            $response = $this->client()->get('/v3/paths/list');
            return $response->successful();
        } catch (ConnectionException $e) {
            return false;
        }
    }

    /**
     * Build the HTTP client with base URL and timeout.
     */
    private function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->withBasicAuth($this->apiUser, $this->apiPass)
            ->acceptJson()
            ->asJson();
    }
}
