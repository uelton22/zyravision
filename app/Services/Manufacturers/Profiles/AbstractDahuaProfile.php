<?php

namespace App\Services\Manufacturers\Profiles;

use App\Contracts\ManufacturerProfile;
use App\DTOs\DevicePorts;
use App\Models\Device;

/**
 * Base profile for Dahua-protocol compatible manufacturers.
 * Intelbras, Dahua, Tecvoz, and Giga share the same RTSP URL pattern.
 */
abstract class AbstractDahuaProfile implements ManufacturerProfile
{
    public function buildRtspUrl(Device $device, int $channel, string $streamType = 'main'): string
    {
        $subtype = match ($streamType) {
            'main' => 0,
            'sub', 'mobile' => 1,
            default => 0,
        };

        $auth = $this->buildAuth($device);
        $host = $device->address;
        $port = $device->port ?: 554;

        return "rtsp://{$auth}{$host}:{$port}/cam/realmonitor?channel={$channel}&subtype={$subtype}";
    }

    public function buildRtmpUrl(Device $device, int $channel, string $streamType = 'main'): string
    {
        $subtype = match ($streamType) {
            'main' => 0,
            'sub', 'mobile' => 1,
            default => 0,
        };

        $auth = $this->buildAuth($device);
        $host = $device->address;
        $port = $device->rtmp_port ?: 1935;

        return "rtmp://{$auth}{$host}:{$port}/cam/realmonitor?channel={$channel}&subtype={$subtype}";
    }

    public function buildSnapshotUrl(Device $device, int $channel): string
    {
        $port = $device->http_port ?: 80;

        return "http://{$device->address}:{$port}/cgi-bin/snapshot.cgi?channel={$channel}";
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
        return [1, 32];
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
        return ['requires_digest_auth' => true];
    }

    protected function buildAuth(Device $device): string
    {
        if (! $device->username) {
            return '';
        }

        $password = $device->password ?? '';

        return urlencode($device->username).':'.urlencode($password).'@';
    }
}
