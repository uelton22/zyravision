# ADR-00029 — Suporte a IPv6 e DDNS para Cameras

**Status:** Aprovado
**Data:** 2026-02-07
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

Em ambientes reais de CFTV, cameras IP e DVRs frequentemente operam em redes com:

- **Enderecos IPv4 privados** atras de NAT (192.168.x.x, 10.x.x, 172.16-31.x.x)
- **Enderecos IPv4 publicos dinamicos** fornecidos por ISPs residenciais/corporativos
- **Redes com pilha dupla IPv4/IPv6** em provedores modernos
- **Redes exclusivamente IPv6** (CGNAT / DS-Lite) onde IPv4 publico nao esta disponivel
- **Servicos de DDNS** (No-IP, DynDNS, DuckDNS, Intelbras DDNS, Hikvision Hik-Connect) para resolver enderecos dinamicos

### Problemas Identificados

1. **CGNAT (Carrier-Grade NAT):** Milhoes de assinantes brasileiros operam sob CGNAT, onde nao ha IPv4 publico individual. IPv6 e a unica forma de acesso direto ao dispositivo.
2. **IPs Dinamicos:** Cameras instaladas em locais remotos (fazendas, obras, filiais) mudam de IP frequentemente. DDNS e essencial para manter conectividade.
3. **Transicao IPv4 para IPv6:** A IANA ja esgotou os blocos IPv4. O suporte nativo a IPv6 e necessario para compatibilidade futura.
4. **Ambientes Mistos:** Clientes corporativos operam redes hibridas IPv4+IPv6 simultaneamente.

---

## Decisao

Implementar **suporte nativo a IPv6 e DDNS** em toda a cadeia de conexao com cameras:

### 1. Camada de Rede — Dual-Stack

```
┌─────────────────────────────────────────────────────────────────┐
│                    ZYRAVISION MEDIA SERVICE                      │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │              Camera Connection Manager                     │   │
│  │                                                            │   │
│  │  ┌────────────┐  ┌────────────┐  ┌─────────────────┐     │   │
│  │  │ IPv4 Stack │  │ IPv6 Stack │  │ DDNS Resolver   │     │   │
│  │  │            │  │            │  │                  │     │   │
│  │  │ 192.168.x  │  │ 2001:db8:: │  │ No-IP, DynDNS  │     │   │
│  │  │ 10.x.x.x  │  │ fe80::     │  │ DuckDNS, Custom │     │   │
│  │  │ NAT/PAT    │  │ Link-Local │  │ Intelbras DDNS  │     │   │
│  │  └──────┬─────┘  └──────┬─────┘  └────────┬────────┘     │   │
│  │         │               │                  │               │   │
│  │         └───────────────┴──────────────────┘               │   │
│  │                         │                                   │   │
│  │              ┌──────────▼──────────┐                       │   │
│  │              │  Unified Transport  │                       │   │
│  │              │  RTSP / ONVIF / HTTP│                       │   │
│  │              └─────────────────────┘                       │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 2. Modelo de Dados — Camera Address

```php
// Campos no cadastro de camera
$camera = [
    'name'            => 'Entrada Principal',
    'address_type'    => 'ipv4|ipv6|ddns|hostname',
    'address'         => '192.168.1.100',       // ou 2001:db8::1 ou camera.no-ip.org
    'port'            => 554,
    'secondary_address' => null,                 // fallback address
    'rtsp_path'       => '/cam/realmonitor?channel=1&subtype=0',
    'transport'       => 'tcp',                  // tcp | udp | auto
    'ddns_provider'   => null,                   // no-ip, dyndns, duckdns, custom
    'ddns_hostname'   => null,
    'ddns_update_interval' => 300,               // segundos
    'ipv6_preferred'  => false,                  // preferir IPv6 quando disponivel
    'last_resolved_ip' => null,                  // cache do ultimo IP resolvido
    'last_resolved_at' => null,
];
```

### 3. Resolucao de Endereco — Address Resolver

```php
// app/Services/Camera/AddressResolver.php

class AddressResolver
{
    /**
     * Resolve o endereco final da camera para conexao.
     * Suporta IPv4, IPv6, DDNS e hostname.
     */
    public function resolve(Camera $camera): ResolvedAddress
    {
        return match ($camera->address_type) {
            'ipv4'     => $this->resolveIPv4($camera),
            'ipv6'     => $this->resolveIPv6($camera),
            'ddns'     => $this->resolveDDNS($camera),
            'hostname' => $this->resolveHostname($camera),
        };
    }

    private function resolveDDNS(Camera $camera): ResolvedAddress
    {
        // 1. Verificar cache (evitar DNS lookup excessivo)
        if ($camera->last_resolved_at?->diffInSeconds(now()) < $camera->ddns_update_interval) {
            return new ResolvedAddress($camera->last_resolved_ip, $camera->port);
        }

        // 2. Resolver via DNS
        $hostname = $camera->ddns_hostname ?? $camera->address;
        $records = dns_get_record($hostname, DNS_A + DNS_AAAA);

        // 3. Preferir IPv6 se configurado
        $ip = $this->selectBestAddress($records, $camera->ipv6_preferred);

        // 4. Atualizar cache
        $camera->updateQuietly([
            'last_resolved_ip' => $ip,
            'last_resolved_at' => now(),
        ]);

        return new ResolvedAddress($ip, $camera->port);
    }

    private function resolveIPv6(Camera $camera): ResolvedAddress
    {
        $address = $camera->address;

        // Tratar enderecos link-local (fe80::) que precisam de scope ID
        if (str_starts_with($address, 'fe80::')) {
            $address = "{$address}%{$camera->network_interface ?? 'eth0'}";
        }

        return new ResolvedAddress($address, $camera->port, 'ipv6');
    }

    private function selectBestAddress(array $records, bool $preferIPv6): string
    {
        $ipv4 = collect($records)->where('type', 'A')->pluck('ip')->first();
        $ipv6 = collect($records)->where('type', 'AAAA')->pluck('ipv6')->first();

        if ($preferIPv6 && $ipv6) return $ipv6;
        if ($ipv4) return $ipv4;
        if ($ipv6) return $ipv6;

        throw new CameraUnreachableException('DNS resolution failed');
    }
}
```

### 4. Conexao RTSP com Dual-Stack

```php
// app/Services/Camera/RtspConnector.php

class RtspConnector
{
    /**
     * Monta a URL RTSP com suporte a IPv6.
     * IPv6 requer colchetes na URL: rtsp://[2001:db8::1]:554/path
     */
    public function buildUrl(ResolvedAddress $address, Camera $camera): string
    {
        $host = $address->isIPv6()
            ? "[{$address->ip}]"
            : $address->ip;

        $auth = '';
        if ($camera->username && $camera->password) {
            $auth = urlencode($camera->username) . ':' . urlencode($camera->password) . '@';
        }

        return "rtsp://{$auth}{$host}:{$address->port}{$camera->rtsp_path}";
    }

    /**
     * Tenta conexao com fallback automatico IPv6 -> IPv4 (ou vice-versa).
     */
    public function connect(Camera $camera): RtspStream
    {
        $resolver = app(AddressResolver::class);
        $primary = $resolver->resolve($camera);

        try {
            return $this->attemptConnection($primary, $camera);
        } catch (ConnectionException $e) {
            // Fallback: tentar endereco secundario
            if ($camera->secondary_address) {
                $secondary = $resolver->resolveSecondary($camera);
                return $this->attemptConnection($secondary, $camera);
            }
            throw $e;
        }
    }
}
```

### 5. Health Check com Suporte Dual-Stack

```php
// app/Services/Camera/HealthChecker.php

class HealthChecker
{
    /**
     * Verifica conectividade da camera via TCP/RTSP.
     * Testa IPv4 e IPv6 quando ambos disponiveis.
     */
    public function check(Camera $camera): HealthStatus
    {
        $resolver = app(AddressResolver::class);
        $address = $resolver->resolve($camera);

        // 1. TCP port check
        $tcpReachable = $this->checkTcpPort($address);

        // 2. RTSP OPTIONS check
        $rtspResponding = $tcpReachable
            ? $this->checkRtspOptions($address, $camera)
            : false;

        // 3. IPv6 connectivity test (se disponivel)
        $ipv6Status = null;
        if ($camera->address_type === 'ddns' || $camera->address_type === 'hostname') {
            $ipv6Status = $this->checkIPv6Connectivity($camera);
        }

        return new HealthStatus(
            reachable: $tcpReachable,
            rtsp_ok: $rtspResponding,
            protocol: $address->protocol,
            latency_ms: $address->latency,
            ipv6_available: $ipv6Status,
            checked_at: now(),
        );
    }
}
```

### 6. DDNS Providers Suportados

| Provider | Tipo | Protocolo Update | Uso Comum |
|----------|------|------------------|-----------|
| No-IP | Comercial | HTTP DynDNS2 | Residencial/Comercial |
| DynDNS | Comercial | HTTP DynDNS2 | Corporativo |
| DuckDNS | Gratuito | HTTP REST | Hobbyista/Pequenas empresas |
| Intelbras DDNS | Fabricante | Proprietario | DVRs Intelbras |
| Hikvision Hik-Connect | Fabricante | Proprietario/P2P | DVRs Hikvision |
| Dahua DMSS | Fabricante | Proprietario/P2P | DVRs Dahua |
| Custom | Configuravel | HTTP/DNS-SD | Qualquer |

### 7. Configuracao de Rede na UI

```typescript
// Frontend: CameraNetworkConfig.vue
interface CameraNetworkConfig {
    address_type: 'ipv4' | 'ipv6' | 'ddns' | 'hostname';
    address: string;
    port: number;
    secondary_address?: string;
    transport: 'tcp' | 'udp' | 'auto';
    ddns_provider?: string;
    ddns_hostname?: string;
    ipv6_preferred: boolean;
}
```

---

## Requisitos de Infraestrutura

### Docker/Kubernetes

```yaml
# docker-compose.yml - Habilitar IPv6
networks:
  frontend:
    driver: bridge
    enable_ipv6: true
    ipam:
      config:
        - subnet: "172.20.0.0/16"
        - subnet: "fd00:zyra::/64"
```

### Nginx (Reverse Proxy)

```nginx
# Escutar em IPv4 e IPv6
server {
    listen 80;
    listen [::]:80;

    listen 443 ssl;
    listen [::]:443 ssl;
}
```

### FFmpeg (Ingestao IPv6)

```bash
# FFmpeg suporta IPv6 nativamente
ffmpeg -i "rtsp://[2001:db8::100]:554/stream1" -c copy output.mp4
```

---

## Seguranca

### Consideracoes Especificas IPv6

| Aspecto | Implementacao |
|---------|---------------|
| Firewall | Regras ip6tables especificas para cameras |
| Scope | Rejeitar conexoes de enderecos link-local (fe80::) vindas de fora da LAN |
| Privacy Extensions | Detectar e alertar sobre enderecos temporarios |
| NDP Spoofing | Monitorar Neighbor Discovery Protocol |
| DDNS Hijacking | Validar certificado TLS do provider DDNS |
| DNS Cache Poisoning | Usar DNSSEC quando disponivel |

### Rate Limiting para DDNS

```php
// Evitar abuso de resolucao DNS
'ddns_resolve' => [
    'max_attempts_per_minute' => 10,
    'cache_ttl_seconds' => 60,
    'stale_cache_ttl_seconds' => 3600, // usar cache stale se DNS falhar
],
```

---

## Consequencias

### Positivas
- Compatibilidade com redes modernas (CGNAT, DS-Lite, IPv6-only)
- Suporte a cameras em locais remotos via DDNS
- Preparacao para o futuro (exaustao IPv4)
- Fallback automatico garante alta disponibilidade
- Compatibilidade com todos os ISPs brasileiros

### Negativas / Trade-offs
- Complexidade adicional na resolucao de enderecos
- Necessidade de testes em redes IPv6 reais
- Nem todos os fabricantes de cameras suportam IPv6 plenamente
- DDNS introduz dependencia de servicos externos

---

## Alternativas Rejeitadas

### Apenas IPv4
- Incompativel com CGNAT (milhoes de usuarios)
- Sem futuro a longo prazo

### VPN para Todas as Cameras
- Complexidade excessiva para instalacoes simples
- Custo operacional elevado
- Latencia adicional

### P2P Relay Proprio
- Custo de infraestrutura muito alto
- Problemas de escala
- Complexidade de NAT traversal

---

## Referencias

- RFC 2460 — Internet Protocol, Version 6 (IPv6)
- RFC 6555 — Happy Eyeballs (Dual-Stack Connection Algorithm)
- RFC 2136 — Dynamic Updates in DNS (DDNS)
- ONVIF Core Specification — Network Configuration
- Intelbras DDNS Technical Documentation
- IANA IPv6 Address Space Registry
