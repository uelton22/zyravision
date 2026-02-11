<?php

namespace App\Services\Camera;

use App\Models\Camera;
use App\Services\Manufacturers\ManufacturerResolver;

class RtspUrlBuilder
{
    public function __construct(
        private ManufacturerResolver $manufacturerResolver,
        private AddressResolver $addressResolver,
    ) {}

    /**
     * Build the full RTSP URL for a camera.
     * If the camera has a custom rtsp_path, use it directly.
     * Otherwise, use the manufacturer profile to construct it.
     */
    public function build(Camera $camera, string $streamType = 'main'): string
    {
        if ($camera->rtsp_path) {
            return $this->buildCustomUrl($camera);
        }

        if ($camera->device) {
            return $camera->device->getRtspUrl(
                $camera->channel_number ?? 1,
                $streamType
            );
        }

        return $this->buildDirectUrl($camera, $streamType);
    }

    private function buildCustomUrl(Camera $camera): string
    {
        $resolved = $this->addressResolver->resolve($camera);
        $host = $resolved->getHostForUrl();
        $auth = $this->buildAuth($camera);

        return "rtsp://{$auth}{$host}:{$resolved->port}{$camera->rtsp_path}";
    }

    private function buildDirectUrl(Camera $camera, string $streamType): string
    {
        $manufacturer = $camera->getEffectiveManufacturer() ?? 'generic';
        $profile = $this->manufacturerResolver->getProfile($manufacturer);
        $resolved = $this->addressResolver->resolve($camera);
        $host = $resolved->getHostForUrl();
        $auth = $this->buildAuth($camera);
        $channel = $camera->channel_number ?? 1;

        $subtype = match ($streamType) {
            'main' => 0,
            'sub', 'mobile' => 1,
            default => 0,
        };

        // Use manufacturer-specific RTSP path pattern
        $rtspPath = match ($manufacturer) {
            'intelbras', 'dahua', 'tecvoz', 'giga' => "/cam/realmonitor?channel={$channel}&subtype={$subtype}",
            'hikvision' => "/Streaming/Channels/{$channel}0" . ($subtype === 1 ? '2' : '1'),
            default => "/ch{$channel}/{$subtype}",
        };

        return "rtsp://{$auth}{$host}:{$resolved->port}{$rtspPath}";
    }

    private function buildAuth(Camera $camera): string
    {
        $username = $camera->username ?? $camera->device?->username;
        $password = $camera->password ?? $camera->device?->password;

        if (! $username) {
            return '';
        }

        return urlencode($username).':'.urlencode($password ?? '').'@';
    }
}
