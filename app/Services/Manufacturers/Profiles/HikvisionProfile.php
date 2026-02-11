<?php

namespace App\Services\Manufacturers\Profiles;

use App\Contracts\ManufacturerProfile;
use App\DTOs\DevicePorts;
use App\Models\Device;

class HikvisionProfile implements ManufacturerProfile
{
    public function getSlug(): string
    {
        return 'hikvision';
    }

    public function getDisplayName(): string
    {
        return 'Hikvision';
    }

    public function getKnownModels(): array
    {
        return [
            'DS-7204HQHI-K1', 'DS-7208HQHI-K1', 'DS-7216HQHI-K2',
            'DS-7604NI-K1', 'DS-7608NI-K2', 'DS-7616NI-K2',
            'DS-7732NI-K4', 'DS-9632NI-I8',
            'DS-2CD2043G2-I', 'DS-2CD2143G2-I',
            'DS-2CD2347G2-LU', 'DS-2CD2T87G2-L',
            'DS-2DE4425IW-DE', 'DS-2DE7A425IW-AEB',
        ];
    }

    /**
     * Hikvision RTSP URL format: /Streaming/Channels/NNN
     * Where NNN = channel * 100 + stream_number
     * Channel 1 Main = 101, Channel 1 Sub = 102
     * Channel 2 Main = 201, Channel 2 Sub = 202
     */
    public function buildRtspUrl(Device $device, int $channel, string $streamType = 'main'): string
    {
        $streamNumber = match ($streamType) {
            'main' => 1,
            'sub', 'mobile' => 2,
            default => 1,
        };

        $channelId = ($channel * 100) + $streamNumber;
        $auth = $this->buildAuth($device);
        $host = $device->address;
        $port = $device->port ?: 554;

        return "rtsp://{$auth}{$host}:{$port}/Streaming/Channels/{$channelId}";
    }

    public function buildRtmpUrl(Device $device, int $channel, string $streamType = 'main'): string
    {
        $streamNumber = match ($streamType) {
            'main' => 1,
            'sub', 'mobile' => 2,
            default => 1,
        };

        $channelId = ($channel * 100) + $streamNumber;
        $auth = $this->buildAuth($device);
        $host = $device->address;
        $port = $device->rtmp_port ?: 1935;

        return "rtmp://{$auth}{$host}:{$port}/Streaming/Channels/{$channelId}";
    }

    public function buildSnapshotUrl(Device $device, int $channel): string
    {
        $port = $device->http_port ?: 80;
        $channelId = ($channel * 100) + 1;

        return "http://{$device->address}:{$port}/ISAPI/Streaming/channels/{$channelId}/picture";
    }

    public function getDefaultPorts(): DevicePorts
    {
        return new DevicePorts(rtsp: 554, http: 80, https: 443, onvif: 80, rtmp: 1935);
    }

    public function getAuthMethod(): string
    {
        return 'digest';
    }

    public function getChannelRange(): array
    {
        return [1, 64];
    }

    public function supportsPtz(): bool
    {
        return true;
    }

    public function supportsAudio(): bool
    {
        return true;
    }

    public function supportsOnvif(): bool
    {
        return true;
    }

    public function getOnvifQuirks(): array
    {
        return [
            'requires_digest_auth' => true,
            'non_standard_time_offset' => true,
        ];
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
