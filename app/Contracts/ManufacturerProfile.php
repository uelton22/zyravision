<?php

namespace App\Contracts;

use App\DTOs\DevicePorts;
use App\Models\Device;

interface ManufacturerProfile
{
    public function getSlug(): string;

    public function getDisplayName(): string;

    public function getKnownModels(): array;

    public function buildRtspUrl(Device $device, int $channel, string $streamType = 'main'): string;

    public function buildSnapshotUrl(Device $device, int $channel): string;

    public function buildRtmpUrl(Device $device, int $channel, string $streamType = 'main'): string;

    public function getDefaultPorts(): DevicePorts;

    public function getAuthMethod(): string;

    public function getChannelRange(): array;

    public function supportsPtz(): bool;

    public function supportsAudio(): bool;

    public function supportsOnvif(): bool;

    public function getOnvifQuirks(): array;
}
