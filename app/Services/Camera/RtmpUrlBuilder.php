<?php

namespace App\Services\Camera;

use App\Models\Camera;
use App\Services\Manufacturers\ManufacturerResolver;

class RtmpUrlBuilder
{
    public function __construct(
        private ManufacturerResolver $manufacturerResolver,
        private AddressResolver $addressResolver,
    ) {}

    public function build(Camera $camera, string $streamType = 'main'): string
    {
        if ($camera->rtmp_path) {
            return $this->buildCustomUrl($camera);
        }

        if ($camera->device) {
            return $camera->device->getRtmpUrl(
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
        $port = $camera->device?->rtmp_port ?? 1935;

        return "rtmp://{$auth}{$host}:{$port}{$camera->rtmp_path}";
    }

    private function buildDirectUrl(Camera $camera, string $streamType): string
    {
        $resolved = $this->addressResolver->resolve($camera);
        $host = $resolved->getHostForUrl();
        $auth = $this->buildAuth($camera);

        return "rtmp://{$auth}{$host}:1935/live/ch1";
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
