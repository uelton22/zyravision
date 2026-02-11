<?php

namespace App\Services\Camera;

use App\DTOs\ResolvedAddress;
use App\Models\Camera;

class AddressResolver
{
    public function resolve(Camera $camera): ResolvedAddress
    {
        $address = $camera->getEffectiveAddress();
        $port = $camera->getEffectivePort();
        $addressType = $camera->address_type ?? $camera->device?->address_type ?? 'ipv4';

        return match ($addressType) {
            'ipv4' => new ResolvedAddress($address, $port, 'ipv4'),
            'ipv6' => new ResolvedAddress($address, $port, 'ipv6'),
            'ddns', 'hostname' => $this->resolveHostname($address, $port),
            default => new ResolvedAddress($address, $port),
        };
    }

    private function resolveHostname(string $hostname, int $port): ResolvedAddress
    {
        $ip = gethostbyname($hostname);
        $type = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'ipv6' : 'ipv4';

        return new ResolvedAddress($ip, $port, $type);
    }
}
