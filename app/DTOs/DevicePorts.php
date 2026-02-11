<?php

namespace App\DTOs;

class DevicePorts
{
    public function __construct(
        public readonly int $rtsp = 554,
        public readonly int $http = 80,
        public readonly int $https = 443,
        public readonly int $onvif = 80,
        public readonly int $rtmp = 1935,
    ) {}

    public function toArray(): array
    {
        return [
            'rtsp' => $this->rtsp,
            'http' => $this->http,
            'https' => $this->https,
            'onvif' => $this->onvif,
            'rtmp' => $this->rtmp,
        ];
    }
}
