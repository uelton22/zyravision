<?php

namespace App\Services\Manufacturers\Profiles;

use App\Contracts\ManufacturerProfile;
use App\DTOs\DevicePorts;
use App\Models\Device;

/**
 * Generic fallback profile for unknown or unsupported manufacturers.
 * Uses the most common RTSP path convention.
 */
class GenericProfile implements ManufacturerProfile
{
    public function getSlug(): string
    {
        return 'generic';
    }

    public function getDisplayName(): string
    {
        return 'Outro / Generico';
    }

    public function getKnownModels(): array
    {
        return [];
    }

    public function buildRtspUrl(Device $device, int $channel, string $streamType = 'main'): string
    {
        $streamIndex = match ($streamType) {
            'main' => 0,
            'sub', 'mobile' => 1,
            default => 0,
        };

        $auth = $this->buildAuth($device);
        $host = $device->address;
        $port = $device->port ?: 554;

        return "rtsp://{$auth}{$host}:{$port}/ch{$channel}/{$streamIndex}";
    }

    public function buildRtmpUrl(Device $device, int $channel, string $streamType = 'main'): string
    {
        $auth = $this->buildAuth($device);
        $host = $device->address;
        $port = $device->rtmp_port ?: 1935;

        return "rtmp://{$auth}{$host}:{$port}/live/ch{$channel}";
    }

    public function buildSnapshotUrl(Device $device, int $channel): string
    {
        $port = $device->http_port ?: 80;

        return "http://{$device->address}:{$port}/snapshot.cgi?channel={$channel}";
    }

    public function getDefaultPorts(): DevicePorts
    {
        return new DevicePorts(rtsp: 554, http: 80, https: 443, onvif: 80, rtmp: 1935);
    }

    public function getAuthMethod(): string
    {
        return 'basic';
    }

    public function getChannelRange(): array
    {
        return [1, 128];
    }

    public function supportsPtz(): bool
    {
        return false;
    }

    public function supportsAudio(): bool
    {
        return false;
    }

    public function supportsOnvif(): bool
    {
        return false;
    }

    public function getOnvifQuirks(): array
    {
        return [];
    }

    private function buildAuth(Device $device): string
    {
        if (! $device->username) {
            return '';
        }

        $password = $device->password ?? '';

        return urlencode($device->username).':'.urlencode($password).'@';
    }
}
