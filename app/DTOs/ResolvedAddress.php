<?php

namespace App\DTOs;

class ResolvedAddress
{
    public function __construct(
        public readonly string $ip,
        public readonly int $port,
        public readonly string $type = 'ipv4',
    ) {}

    public function isIPv6(): bool
    {
        return $this->type === 'ipv6' || str_contains($this->ip, ':');
    }

    public function getHostForUrl(): string
    {
        return $this->isIPv6() ? "[{$this->ip}]" : $this->ip;
    }
}
