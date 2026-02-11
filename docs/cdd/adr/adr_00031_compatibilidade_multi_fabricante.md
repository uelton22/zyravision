# ADR-00031 — Compatibilidade Multi-Fabricante de Cameras e DVRs

**Status:** Aprovado
**Data:** 2026-02-07
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

O mercado de CFTV e extremamente fragmentado, com dezenas de fabricantes, cada um com:

- **Paths RTSP proprietarios** diferentes para acesso aos streams de video
- **APIs HTTP/REST** proprietarias para configuracao e controle
- **Niveis variados de compatibilidade ONVIF** (Profile S, T, G, Q)
- **Protocolos de discovery** proprios (Intelbras ConfigTool, Hikvision SADP, Dahua ConfigTool)
- **Formatos de autenticacao** distintos (Basic, Digest, proprietario)
- **Capacidades diferentes** de resolucao, codec, PTZ, audio, alarmes

Para que o ZyraVision seja **viavel comercialmente**, deve suportar os fabricantes mais utilizados no mercado brasileiro e internacional, sem depender de SDKs proprietarios pagos.

---

## Decisao

Implementar uma **arquitetura baseada em Manufacturer Profiles** — um sistema de perfis de configuracao por fabricante que encapsula as particularidades de cada marca em classes isoladas, usando RTSP/ONVIF como protocolo primario e APIs HTTP como fallback.

### 1. Fabricantes Suportados

#### Tier 1 — Prioridade Maxima (Mercado Brasileiro)

| Fabricante | Origem | Protocolos | Quota Mercado BR |
|-----------|--------|------------|------------------|
| **Intelbras** | Brasil | RTSP, ONVIF, API propria | ~40% |
| **Hikvision** | China | RTSP, ONVIF, ISAPI | ~25% |
| **Dahua** | China | RTSP, ONVIF, API propria | ~15% |
| **Giga** | Brasil | RTSP, ONVIF | ~5% |
| **Tecvoz** | Brasil | RTSP, ONVIF | ~3% |

#### Tier 2 — Alta Prioridade

| Fabricante | Origem | Protocolos | Segmento |
|-----------|--------|------------|----------|
| **AXIS** | Suecia | RTSP, ONVIF (referencia), VAPIX | Enterprise |
| **Bosch** | Alemanha | RTSP, ONVIF, BVIP | Enterprise |
| **Hanwha (Samsung)** | Coreia | RTSP, ONVIF, Wisenet API | Enterprise |
| **Uniview** | China | RTSP, ONVIF | Mid-range |
| **JFL** | Brasil | RTSP, ONVIF | Residencial |
| **CPPlus** | India | RTSP, ONVIF | Mid-range |

#### Tier 3 — Suporte Padrao

| Fabricante | Origem | Protocolos |
|-----------|--------|------------|
| **ALIVE** | Brasil | RTSP, ONVIF |
| **AMCREST** | EUA | RTSP, ONVIF |
| **AVTech** | Taiwan | RTSP, ONVIF |
| **Avigilon** | Canada | RTSP, ONVIF |
| **CLEAR** | Brasil | RTSP |
| **D-Link** | Taiwan | RTSP, ONVIF |
| **Eneo** | Alemanha | RTSP, ONVIF |
| **FICO** | Brasil | RTSP |
| **FOSCAM** | China | RTSP, ONVIF, CGI |
| **GREATEK** | Brasil | RTSP |
| **GeoVision** | Taiwan | RTSP, ONVIF |
| **HDL** | Brasil | RTSP, ONVIF |
| **LG** | Coreia | RTSP, ONVIF |
| **Luxvision** | Brasil | RTSP, ONVIF |
| **MULTILASER** | Brasil | RTSP, ONVIF |
| **Panasonic** | Japao | RTSP, ONVIF, CGI |
| **TRENDnet** | EUA | RTSP, ONVIF |
| **TWG** | Brasil | RTSP |
| **Tenda** | China | RTSP |
| **VENETIAN** | Brasil | RTSP |
| **VIVOTEK** | Taiwan | RTSP, ONVIF |
| **Viewtron** | EUA | RTSP, ONVIF |
| **ZAVIO** | Taiwan | RTSP, ONVIF |
| **JORTAN** | China | RTSP |

#### Generic — Qualquer Fabricante

| Modo | Descricao |
|------|-----------|
| **GENERIC RTSP** | URL RTSP customizada, sem perfil especifico |
| **GENERIC ONVIF** | Discovery e configuracao via ONVIF padrao |

---

### 2. Arquitetura — Manufacturer Profile System

```
┌─────────────────────────────────────────────────────────────────┐
│                    MANUFACTURER PROFILE SYSTEM                    │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │              ManufacturerResolver (Factory)                 │  │
│  │  resolve('intelbras') -> IntelbrasProfile                  │  │
│  │  resolve('hikvision') -> HikvisionProfile                  │  │
│  │  resolve('generic')   -> GenericProfile                    │  │
│  └──────────────────────────┬────────────────────────────────┘  │
│                              │                                   │
│  ┌───────────────────────────▼───────────────────────────────┐  │
│  │              ManufacturerProfile (Interface)                │  │
│  │                                                            │  │
│  │  + buildRtspUrl(device, channel, streamType): string       │  │
│  │  + buildSnapshotUrl(device, channel): string               │  │
│  │  + getDefaultPorts(): DevicePorts                          │  │
│  │  + getAuthMethod(): string                                 │  │
│  │  + getChannelRange(): [int, int]                           │  │
│  │  + supportsPtz(): bool                                     │  │
│  │  + supportsAudio(): bool                                   │  │
│  │  + supportsOnvif(): bool                                   │  │
│  │  + getOnvifQuirks(): array                                 │  │
│  │  + getDeviceInfo(device): DeviceInfo                       │  │
│  │  + getPtzCommands(): PtzCommandSet                         │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  Implementacoes:                                                 │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │Intelbras │ │Hikvision │ │  Dahua   │ │  Axis    │           │
│  │ Profile  │ │ Profile  │ │ Profile  │ │ Profile  │           │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │  Giga    │ │ Tecvoz   │ │   JFL    │ │ Generic  │           │
│  │ Profile  │ │ Profile  │ │ Profile  │ │ Profile  │           │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘           │
└─────────────────────────────────────────────────────────────────┘
```

### 3. Interface ManufacturerProfile

```php
// app/Contracts/ManufacturerProfile.php

interface ManufacturerProfile
{
    /** Identificador unico do fabricante (slug). */
    public function getSlug(): string;

    /** Nome de exibicao do fabricante. */
    public function getDisplayName(): string;

    /** Modelos conhecidos deste fabricante. */
    public function getKnownModels(): array;

    /**
     * Monta a URL RTSP para um canal especifico.
     *
     * @param streamType 'main' = stream principal (alta qualidade)
     *                   'sub'  = sub-stream (baixa qualidade, para grid)
     *                   'mobile' = stream otimizado para mobile
     */
    public function buildRtspUrl(Device $device, int $channel, string $streamType = 'main'): string;

    /** URL para captura de snapshot JPEG do canal. */
    public function buildSnapshotUrl(Device $device, int $channel): string;

    /** Portas padrao do fabricante. */
    public function getDefaultPorts(): DevicePorts;

    /** Metodo de autenticacao (basic, digest, proprietary). */
    public function getAuthMethod(): string;

    /** Range de canais suportados [min, max]. */
    public function getChannelRange(): array;

    /** Se suporta controle PTZ. */
    public function supportsPtz(): bool;

    /** Se suporta audio bidirecional. */
    public function supportsAudio(): bool;

    /** Se suporta ONVIF e qual versao. */
    public function supportsOnvif(): bool;

    /** Quirks conhecidos do ONVIF deste fabricante. */
    public function getOnvifQuirks(): array;
}
```

### 4. Implementacoes dos Perfis Principais

```php
// app/Services/Manufacturers/IntelbrasProfile.php

class IntelbrasProfile implements ManufacturerProfile
{
    public function getSlug(): string { return 'intelbras'; }
    public function getDisplayName(): string { return 'Intelbras'; }

    public function getKnownModels(): array
    {
        return [
            // DVRs
            'MHDX 1004-C', 'MHDX 1008-C', 'MHDX 1016-C',
            'MHDX 3004-C', 'MHDX 3008-C', 'MHDX 3016',
            'MHDX 5016', 'MHDX 5032', 'MHDX 7016', 'MHDX 7032',
            // NVRs
            'NVD 1404', 'NVD 1408', 'NVD 1416',
            'NVD 3304-C', 'NVD 3308-C', 'NVD 3316-C',
            // Cameras IP
            'VIP 1220 D', 'VIP 1230 B', 'VIP 3220 D',
            'VIP 3230 B', 'VIP 5280 B', 'VIP 7280 Z',
        ];
    }

    /**
     * Intelbras usa o padrao Dahua para RTSP:
     * rtsp://user:pass@ip:554/cam/realmonitor?channel=N&subtype=S
     * subtype: 0 = main stream, 1 = sub stream
     */
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

    public function buildSnapshotUrl(Device $device, int $channel): string
    {
        $port = $device->http_port ?: 80;
        return "http://{$device->address}:{$port}/cgi-bin/snapshot.cgi?channel={$channel}";
    }

    public function getDefaultPorts(): DevicePorts
    {
        return new DevicePorts(rtsp: 554, http: 80, https: 443, onvif: 80);
    }

    public function getAuthMethod(): string { return 'digest'; }
    public function getChannelRange(): array { return [1, 32]; }
    public function supportsPtz(): bool { return true; }
    public function supportsAudio(): bool { return true; }
    public function supportsOnvif(): bool { return true; }

    public function getOnvifQuirks(): array
    {
        return [
            'requires_digest_auth' => true,
            'onvif_path' => '/onvif/device_service',
            'ptz_continuous_move' => true,
        ];
    }

    private function buildAuth(Device $device): string
    {
        if (!$device->username) return '';
        return urlencode($device->username) . ':' . urlencode(decrypt($device->password)) . '@';
    }
}
```

```php
// app/Services/Manufacturers/HikvisionProfile.php

class HikvisionProfile implements ManufacturerProfile
{
    public function getSlug(): string { return 'hikvision'; }
    public function getDisplayName(): string { return 'Hikvision'; }

    public function getKnownModels(): array
    {
        return [
            // DVRs
            'DS-7204HGHI', 'DS-7208HGHI', 'DS-7216HGHI',
            'DS-7204HUHI', 'DS-7208HUHI', 'DS-7216HUHI',
            'DS-7304HUHI', 'DS-7308HUHI', 'DS-7316HUHI',
            'DS-7332HUHI', 'DS-9632NI',
            // NVRs
            'DS-7604NI', 'DS-7608NI', 'DS-7616NI', 'DS-7632NI',
            // Cameras
            'DS-2CD1021', 'DS-2CD1043', 'DS-2CD2021', 'DS-2CD2043',
            'DS-2CD2385', 'DS-2CD2T47', 'DS-2DE4425IW',
        ];
    }

    /**
     * Hikvision RTSP path:
     * rtsp://user:pass@ip:554/Streaming/Channels/NNN
     * Canal: 101 = ch1 main, 102 = ch1 sub
     *        201 = ch2 main, 202 = ch2 sub
     */
    public function buildRtspUrl(Device $device, int $channel, string $streamType = 'main'): string
    {
        $streamId = match ($streamType) {
            'main' => 1,
            'sub', 'mobile' => 2,
            default => 1,
        };

        $channelCode = ($channel * 100) + $streamId;
        $auth = $this->buildAuth($device);
        $port = $device->port ?: 554;

        return "rtsp://{$auth}{$device->address}:{$port}/Streaming/Channels/{$channelCode}";
    }

    public function buildSnapshotUrl(Device $device, int $channel): string
    {
        $port = $device->http_port ?: 80;
        return "http://{$device->address}:{$port}/ISAPI/Streaming/channels/{$channel}01/picture";
    }

    public function getDefaultPorts(): DevicePorts
    {
        return new DevicePorts(rtsp: 554, http: 80, https: 443, onvif: 80);
    }

    public function getAuthMethod(): string { return 'digest'; }
    public function getChannelRange(): array { return [1, 64]; }
    public function supportsPtz(): bool { return true; }
    public function supportsAudio(): bool { return true; }
    public function supportsOnvif(): bool { return true; }

    public function getOnvifQuirks(): array
    {
        return [
            'requires_digest_auth' => true,
            'onvif_path' => '/onvif/device_service',
            'event_pull_point' => true,
            'ptz_relative_move' => true,
        ];
    }

    private function buildAuth(Device $device): string
    {
        if (!$device->username) return '';
        return urlencode($device->username) . ':' . urlencode(decrypt($device->password)) . '@';
    }
}
```

```php
// app/Services/Manufacturers/DahuaProfile.php

class DahuaProfile implements ManufacturerProfile
{
    public function getSlug(): string { return 'dahua'; }
    public function getDisplayName(): string { return 'Dahua'; }

    /**
     * Dahua RTSP path (mesmo padrao que Intelbras):
     * rtsp://user:pass@ip:554/cam/realmonitor?channel=N&subtype=S
     */
    public function buildRtspUrl(Device $device, int $channel, string $streamType = 'main'): string
    {
        $subtype = match ($streamType) {
            'main' => 0,
            'sub', 'mobile' => 1,
            default => 0,
        };

        $auth = $this->buildAuth($device);
        $port = $device->port ?: 554;

        return "rtsp://{$auth}{$device->address}:{$port}/cam/realmonitor?channel={$channel}&subtype={$subtype}";
    }

    // ... demais metodos similares ao Intelbras
    public function getDefaultPorts(): DevicePorts
    {
        return new DevicePorts(rtsp: 554, http: 80, https: 443, onvif: 80);
    }

    public function getAuthMethod(): string { return 'digest'; }
    public function getChannelRange(): array { return [1, 64]; }
    public function supportsPtz(): bool { return true; }
    public function supportsAudio(): bool { return true; }
    public function supportsOnvif(): bool { return true; }
    public function getOnvifQuirks(): array { return ['requires_digest_auth' => true]; }

    private function buildAuth(Device $device): string
    {
        if (!$device->username) return '';
        return urlencode($device->username) . ':' . urlencode(decrypt($device->password)) . '@';
    }
}
```

```php
// app/Services/Manufacturers/GenericProfile.php

class GenericProfile implements ManufacturerProfile
{
    public function getSlug(): string { return 'generic'; }
    public function getDisplayName(): string { return 'Generic (RTSP/ONVIF)'; }

    /**
     * Generic: usuario fornece a URL RTSP completa ou usa padrao basico.
     * Tenta o padrao mais comum: /stream1, /ch01/0, /live
     */
    public function buildRtspUrl(Device $device, int $channel, string $streamType = 'main'): string
    {
        $auth = $this->buildAuth($device);
        $port = $device->port ?: 554;
        $stream = $streamType === 'main' ? '0' : '1';

        // Padrao generico mais compativel
        return "rtsp://{$auth}{$device->address}:{$port}/ch{$channel}/{$stream}";
    }

    public function getDefaultPorts(): DevicePorts
    {
        return new DevicePorts(rtsp: 554, http: 80, https: 443, onvif: 80);
    }

    public function getAuthMethod(): string { return 'digest'; }
    public function getChannelRange(): array { return [1, 128]; }
    public function supportsPtz(): bool { return false; }
    public function supportsAudio(): bool { return false; }
    public function supportsOnvif(): bool { return true; }
    public function getOnvifQuirks(): array { return []; }

    private function buildAuth(Device $device): string
    {
        if (!$device->username) return '';
        return urlencode($device->username) . ':' . urlencode(decrypt($device->password)) . '@';
    }
}
```

### 5. ManufacturerResolver — Factory

```php
// app/Services/Manufacturers/ManufacturerResolver.php

class ManufacturerResolver
{
    private array $profiles = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    public function getProfile(string $manufacturer): ManufacturerProfile
    {
        $slug = Str::slug($manufacturer);
        return $this->profiles[$slug] ?? $this->profiles['generic'];
    }

    public function getAllProfiles(): array
    {
        return $this->profiles;
    }

    /**
     * Retorna lista de fabricantes para select no frontend.
     */
    public function getManufacturerList(): array
    {
        return collect($this->profiles)
            ->map(fn ($p) => [
                'value' => $p->getSlug(),
                'label' => $p->getDisplayName(),
                'onvif' => $p->supportsOnvif(),
                'ptz' => $p->supportsPtz(),
                'audio' => $p->supportsAudio(),
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }

    private function registerDefaults(): void
    {
        $classes = [
            // Tier 1
            IntelbrasProfile::class,
            HikvisionProfile::class,
            DahuaProfile::class,
            GigaProfile::class,
            TecvozProfile::class,
            // Tier 2
            AxisProfile::class,
            BoschProfile::class,
            HanwhaProfile::class,
            UnivewProfile::class,
            JflProfile::class,
            CpPlusProfile::class,
            // Tier 3
            AliveProfile::class,
            AmcrestProfile::class,
            AvtechProfile::class,
            AvigilonProfile::class,
            ClearProfile::class,
            DlinkProfile::class,
            EneoProfile::class,
            FicoProfile::class,
            FoscamProfile::class,
            GreatekProfile::class,
            GeovisionProfile::class,
            HdlProfile::class,
            LgProfile::class,
            LuxvisionProfile::class,
            MultilaserProfile::class,
            PanasonicProfile::class,
            TrendnetProfile::class,
            TwgProfile::class,
            TendaProfile::class,
            VenetianProfile::class,
            VivotekProfile::class,
            ViewtronProfile::class,
            ZavioProfile::class,
            JortanProfile::class,
            // Generic (fallback)
            GenericProfile::class,
        ];

        foreach ($classes as $class) {
            $profile = new $class();
            $this->profiles[$profile->getSlug()] = $profile;
        }
    }
}
```

### 6. Tabela de Referencia RTSP por Fabricante

| Fabricante | Path RTSP (Main Stream) | Path RTSP (Sub Stream) | Auth |
|-----------|-------------------------|------------------------|------|
| **Intelbras** | `/cam/realmonitor?channel=N&subtype=0` | `...&subtype=1` | Digest |
| **Hikvision** | `/Streaming/Channels/N01` | `/Streaming/Channels/N02` | Digest |
| **Dahua** | `/cam/realmonitor?channel=N&subtype=0` | `...&subtype=1` | Digest |
| **AXIS** | `/axis-media/media.amp?videocodec=h264&camera=N` | idem com resolution | Digest |
| **Bosch** | `/rtsp_tunnel?h26x=4&line=N&inst=1` | `...&inst=2` | Digest |
| **Hanwha** | `/profile2/media.smp` (ONVIF) | Profile token | Digest |
| **Uniview** | `/media/video1` (ch1), `/media/video2` | `.../video3` | Digest |
| **Giga** | `/cam/realmonitor?channel=N&subtype=0` | `...&subtype=1` | Digest |
| **Tecvoz** | `/cam/realmonitor?channel=N&subtype=0` | `...&subtype=1` | Digest |
| **JFL** | `/cam/realmonitor?channel=N&subtype=0` | `...&subtype=1` | Digest |
| **FOSCAM** | `/videoMain` | `/videoSub` | Basic |
| **D-Link** | `/play1.sdp` (ch1) | `/play2.sdp` | Basic |
| **Panasonic** | `/MediaInput/stream_1` | `/MediaInput/stream_2` | Digest |
| **VIVOTEK** | `/live.sdp` | `/live2.sdp` | Digest |
| **TRENDnet** | `/v1/ch1` | `/v1/ch1s` | Basic |
| **AMCREST** | `/cam/realmonitor?channel=N&subtype=0` | `...&subtype=1` | Digest |
| **GeoVision** | `/CH00N.sdp` | `/CH00N_sub.sdp` | Basic |
| **GENERIC** | `/ch{N}/{stream}` ou URL customizada | configuravel | Auto |

### 7. Sistema de Auto-Detect de Fabricante

```php
// app/Services/Manufacturers/ManufacturerDetector.php

class ManufacturerDetector
{
    /**
     * Tenta identificar o fabricante automaticamente via:
     * 1. ONVIF GetDeviceInformation
     * 2. HTTP headers do servidor web
     * 3. UPnP/SSDP discovery
     * 4. MAC address OUI lookup
     */
    public function detect(string $address, int $httpPort = 80): ?DetectionResult
    {
        // 1. ONVIF
        $onvifResult = $this->tryOnvifDetection($address, $httpPort);
        if ($onvifResult) return $onvifResult;

        // 2. HTTP fingerprint
        $httpResult = $this->tryHttpFingerprint($address, $httpPort);
        if ($httpResult) return $httpResult;

        // 3. RTSP OPTIONS banner
        $rtspResult = $this->tryRtspBanner($address, 554);
        if ($rtspResult) return $rtspResult;

        return null;
    }

    private function tryHttpFingerprint(string $address, int $port): ?DetectionResult
    {
        $response = Http::timeout(5)->get("http://{$address}:{$port}/");
        $server = $response->header('Server') ?? '';
        $body = $response->body();

        // Fingerprints conhecidos
        $fingerprints = [
            'intelbras' => ['Intelbras', 'MHDX', 'NVD', 'VIP'],
            'hikvision' => ['Hikvision', 'DNVRS', 'ISAPI', 'DS-'],
            'dahua'     => ['Dahua', 'DH-', 'DMSS'],
            'axis'      => ['AXIS', 'axis-media'],
            'bosch'     => ['Bosch', 'BVIP'],
            'foscam'    => ['Foscam', 'FOSCAM'],
        ];

        foreach ($fingerprints as $manufacturer => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($server . $body, $keyword)) {
                    return new DetectionResult(
                        manufacturer: $manufacturer,
                        confidence: 0.85,
                        method: 'http_fingerprint',
                    );
                }
            }
        }

        return null;
    }
}
```

---

## UI — Selecao de Fabricante

```typescript
// Frontend: ManufacturerSelect.vue
interface ManufacturerOption {
    value: string;       // 'intelbras'
    label: string;       // 'Intelbras'
    onvif: boolean;
    ptz: boolean;
    audio: boolean;
}

// Agrupamento visual na UI:
// [Tier 1 - Populares]
//   Intelbras, Hikvision, Dahua, Giga, Tecvoz
// [Tier 2 - Enterprise]
//   AXIS, Bosch, Hanwha, Uniview, JFL, CPPlus
// [Tier 3 - Outros]
//   ALIVE, AMCREST, AVTech, ... (alfabetico)
// [Generic]
//   GENERIC (RTSP/ONVIF customizado)
```

---

## Testes de Compatibilidade

### Matriz de Testes

| Fabricante | Conexao RTSP | Snapshot | ONVIF Discovery | PTZ | Audio | Status |
|-----------|:------------:|:--------:|:---------------:|:---:|:-----:|--------|
| Intelbras | Obrigatorio | Obrigatorio | Obrigatorio | Se suportar | Se suportar | Tier 1 |
| Hikvision | Obrigatorio | Obrigatorio | Obrigatorio | Se suportar | Se suportar | Tier 1 |
| Dahua | Obrigatorio | Obrigatorio | Obrigatorio | Se suportar | Se suportar | Tier 1 |
| Giga | Obrigatorio | Opcional | Opcional | - | - | Tier 1 |
| Tecvoz | Obrigatorio | Opcional | Opcional | - | - | Tier 1 |
| AXIS | Obrigatorio | Obrigatorio | Obrigatorio | Obrigatorio | Obrigatorio | Tier 2 |
| Generic | Obrigatorio | Opcional | Opcional | - | - | Fallback |

### Ambiente de Testes

```yaml
# docker-compose.test-cameras.yml
# Simulador de cameras para testes automatizados
services:
  rtsp-simulator:
    image: aler9/rtsp-simple-server
    ports:
      - "8554:8554"
    volumes:
      - ./tests/fixtures/video:/data
```

---

## Consequencias

### Positivas
- Compatibilidade com 95%+ dos equipamentos do mercado brasileiro
- Sistema extensivel: adicionar novo fabricante = criar nova classe Profile
- Auto-detect reduz erros de configuracao do usuario
- Tabela de referencia RTSP simplifica suporte tecnico
- Perfil Generic garante que qualquer camera RTSP funcione

### Negativas / Trade-offs
- Manutencao de 35+ perfis de fabricantes
- Fabricantes podem mudar paths RTSP entre firmwares
- Testes exigem acesso a equipamentos reais ou simuladores
- ONVIF nunca e 100% padronizado entre fabricantes

---

## Alternativas Rejeitadas

### SDK Proprietario por Fabricante
- Custo proibitivo (licenciamento por canal)
- Complexidade de manter dezenas de SDKs nativos
- Incompatibilidade entre plataformas (muitos SDKs sao Windows-only)

### Apenas ONVIF
- Muitos DVRs baratos nao implementam ONVIF
- Implementacoes ONVIF inconsistentes entre fabricantes
- Funcionalidades avancadas muitas vezes nao expostas via ONVIF

### URL RTSP Manual (Sem Perfis)
- Experiencia ruim para o usuario (precisa saber o path exato)
- Alta taxa de erro na configuracao
- Sem possibilidade de auto-discovery

---

## ADRs Relacionadas

- ADR-0006 — Pipeline de Video (RTSP, HLS, WebRTC)
- ADR-0029 — Suporte a IPv6 e DDNS
- ADR-0030 — Suporte a DVRs como Fontes de Camera
- ADR-0025 — Integracoes com Sistemas Externos

---

## Referencias

- ONVIF Conformance Testing Tool
- RTSP Protocol Reference (RFC 7826)
- Intelbras Developer Documentation
- Hikvision ISAPI Reference Guide
- Dahua HTTP API Documentation
- AXIS VAPIX Library
- Bosch BVIP Communication Protocol
- IP Camera RTSP URL Directory (ispyconnect.com)
