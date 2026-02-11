<?php

namespace App\Services\Camera;

use App\Models\Camera;

class StreamUrlResolver
{
    public function __construct(
        private RtspUrlBuilder $rtspUrlBuilder,
        private RtmpUrlBuilder $rtmpUrlBuilder,
    ) {}

    public function resolve(Camera $camera): array
    {
        if (! $camera->relationLoaded('activeStream')) {
            $camera->load('activeStream');
        }

        return [
            'rtsp_url' => $this->buildRtspUrl($camera),
            'rtmp_url' => $this->buildRtmpUrl($camera),
            'hls_url' => $this->buildHlsUrl($camera),
            'hls_shift_url' => null,
            'mp4_export_url' => null,
        ];
    }

    private function buildRtspUrl(Camera $camera): ?string
    {
        try {
            return $this->rtspUrlBuilder->build($camera, $camera->stream_type ?? 'main');
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildRtmpUrl(Camera $camera): ?string
    {
        try {
            return $this->rtmpUrlBuilder->build($camera, $camera->stream_type ?? 'main');
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildHlsUrl(Camera $camera): ?string
    {
        $activeStream = $camera->activeStream;

        if ($activeStream && $activeStream->isRunning()) {
            return $activeStream->hls_url;
        }

        return null;
    }
}
