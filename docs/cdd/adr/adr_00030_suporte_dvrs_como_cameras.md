# ADR-00030 — Suporte a DVRs/NVRs como Fontes de Camera

**Status:** Aprovado
**Data:** 2026-02-07
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

No mercado brasileiro de CFTV, a grande maioria das instalacoes utiliza **DVRs (Digital Video Recorders)** e **NVRs (Network Video Recorders)** como ponto central de conexao das cameras. Estes equipamentos:

- Agregam de **4 a 128 canais** de video em um unico dispositivo
- Sao fabricados por Intelbras, Hikvision, Dahua, Tecvoz, Giga, JFL, entre outros
- Expoe cada canal como um **stream RTSP individual** com paths especificos por fabricante
- Possuem **interfaces web** para configuracao (HTTP/HTTPS)
- Implementam **ONVIF** parcialmente (nem todos os fabricantes sao totalmente compativeis)
- Podem operar em modo **standalone** (gravacao local) ou como **gateway** de cameras

### Cenarios de Uso

1. **DVR como Gateway:** Cliente ja possui DVR com 16 cameras analogicas. ZyraVision conecta ao DVR e recebe os 16 canais como cameras individuais.
2. **NVR Hibrido:** NVR com cameras IP conectadas. ZyraVision conecta diretamente ao NVR para acessar os canais.
3. **DVR Legado:** DVR antigo sem ONVIF. Conexao apenas via RTSP com paths proprietarios.
4. **DVR + Cameras IP Diretas:** Ambiente misto onde o ZyraVision gerencia tanto cameras diretas quanto canais de DVR.
5. **Multiplos DVRs:** Grande instalacao com varios DVRs em diferentes locais, todos gerenciados pelo ZyraVision.

---

## Decisao

Implementar uma **abstracacao de dispositivo (Device)** que separa o conceito de "equipamento fisico" (DVR/NVR/Camera IP) do conceito de "canal de video" (Camera), permitindo que um unico DVR exponha multiplos canais como cameras individuais.

### 1. Modelo de Dados — Device + Camera Channel

```
┌─────────────────────────────────────────────────────────────────┐
│                       DEVICE (DVR/NVR)                           │
│  ID: dvr_001                                                     │
│  Tipo: DVR                                                       │
│  Fabricante: Intelbras                                           │
│  Modelo: MHDX 3116                                               │
│  IP: 192.168.1.200                                               │
│  Canais Totais: 16                                               │
│                                                                  │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐     ┌────────┐   │
│  │ CH 01  │ │ CH 02  │ │ CH 03  │ │ CH 04  │ ... │ CH 16  │   │
│  │Camera 1│ │Camera 2│ │Camera 3│ │Camera 4│     │Camera16│   │
│  │Entrada │ │Estac.  │ │Portaria│ │Recepção│     │Depósito│   │
│  └────────┘ └────────┘ └────────┘ └────────┘     └────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 2. Estrutura de Tabelas

```php
// Migration: create_devices_table.php

Schema::create('devices', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();

    // Identificacao
    $table->string('name');                          // "DVR Matriz"
    $table->string('slug')->unique();
    $table->enum('type', [
        'dvr',      // Digital Video Recorder
        'nvr',      // Network Video Recorder
        'encoder',  // Video Encoder (analogico -> IP)
        'camera',   // Camera IP standalone
        'hybrid',   // DVR Hibrido (analog + IP)
    ]);

    // Fabricante
    $table->string('manufacturer');                  // "intelbras"
    $table->string('model')->nullable();             // "MHDX 3116"
    $table->string('firmware_version')->nullable();

    // Conexao
    $table->string('address_type');                  // ipv4, ipv6, ddns, hostname
    $table->string('address');                       // IP ou hostname
    $table->integer('port')->default(554);           // Porta RTSP
    $table->integer('http_port')->default(80);       // Porta web
    $table->string('secondary_address')->nullable(); // Fallback
    $table->string('transport')->default('tcp');

    // Autenticacao
    $table->string('username')->nullable();
    $table->text('password')->nullable();            // Encrypted

    // Capacidade
    $table->integer('total_channels')->default(1);   // Canais do DVR
    $table->integer('active_channels')->default(0);  // Canais em uso

    // ONVIF
    $table->boolean('onvif_supported')->default(false);
    $table->string('onvif_profile')->nullable();     // Profile S, T, G

    // Status
    $table->boolean('is_active')->default(true);
    $table->boolean('is_online')->default(false);
    $table->timestamp('last_seen_at')->nullable();
    $table->integer('uptime_seconds')->default(0);

    // DDNS
    $table->string('ddns_provider')->nullable();
    $table->string('ddns_hostname')->nullable();
    $table->boolean('ipv6_preferred')->default(false);
    $table->string('last_resolved_ip')->nullable();
    $table->timestamp('last_resolved_at')->nullable();

    // Metadata
    $table->string('location')->nullable();          // "Predio A - Sala TI"
    $table->string('serial_number')->nullable();
    $table->jsonb('metadata')->default('{}');

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'type']);
    $table->index(['manufacturer']);
});
```

```php
// Camera table - adicionar device_id

Schema::table('cameras', function (Blueprint $table) {
    $table->foreignUuid('device_id')->nullable()->constrained()->nullOnDelete();
    $table->integer('channel_number')->nullable();  // Canal no DVR (1-128)
    $table->enum('channel_type', [
        'analog',   // Camera analogica conectada ao DVR
        'ip',       // Camera IP via NVR
        'direct',   // Camera IP direta (sem DVR)
    ])->default('direct');
});
```

### 3. Relationships

```php
// app/Models/Device.php

class Device extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class);
    }

    public function activeChannels(): HasMany
    {
        return $this->hasMany(Camera::class)->where('is_active', true);
    }

    /**
     * Gera a URL RTSP para um canal especifico do DVR.
     * Cada fabricante tem um path RTSP diferente.
     */
    public function getRtspUrl(int $channel, string $streamType = 'main'): string
    {
        $resolver = app(ManufacturerResolver::class);
        $profile = $resolver->getProfile($this->manufacturer);

        return $profile->buildRtspUrl($this, $channel, $streamType);
    }

    public function isDvr(): bool
    {
        return in_array($this->type, ['dvr', 'nvr', 'hybrid']);
    }
}
```

### 4. Auto-Discovery de Canais do DVR

```php
// app/Services/Device/ChannelDiscovery.php

class ChannelDiscovery
{
    /**
     * Descobre automaticamente os canais disponiveis em um DVR/NVR.
     * Usa ONVIF quando disponivel, fallback para teste RTSP.
     */
    public function discover(Device $device): DiscoveryResult
    {
        // 1. Tentar ONVIF GetProfiles
        if ($device->onvif_supported) {
            return $this->discoverViaOnvif($device);
        }

        // 2. Fallback: Testar canais RTSP sequencialmente
        return $this->discoverViaRtsp($device);
    }

    private function discoverViaRtsp(Device $device): DiscoveryResult
    {
        $profile = app(ManufacturerResolver::class)->getProfile($device->manufacturer);
        $channels = [];

        for ($ch = 1; $ch <= $device->total_channels; $ch++) {
            $url = $profile->buildRtspUrl($device, $ch, 'main');
            $reachable = $this->testRtspConnection($url, timeout: 5);

            $channels[] = new DiscoveredChannel(
                number: $ch,
                active: $reachable,
                main_stream_url: $url,
                sub_stream_url: $profile->buildRtspUrl($device, $ch, 'sub'),
            );
        }

        return new DiscoveryResult(
            device: $device,
            channels: $channels,
            discovered_at: now(),
        );
    }

    /**
     * Cria cameras automaticamente a partir dos canais descobertos.
     */
    public function autoCreateCameras(Device $device, array $selectedChannels): array
    {
        $created = [];

        foreach ($selectedChannels as $channel) {
            $camera = Camera::create([
                'tenant_id' => $device->tenant_id,
                'device_id' => $device->id,
                'name' => "{$device->name} - CH{$channel['number']}",
                'channel_number' => $channel['number'],
                'channel_type' => $device->type === 'nvr' ? 'ip' : 'analog',
                'address' => $device->address,
                'port' => $device->port,
                'username' => $device->username,
                'password' => $device->password,
                'rtsp_path' => $channel['rtsp_path'],
                'is_active' => true,
            ]);
            $created[] = $camera;
        }

        // Atualizar contador de canais ativos
        $device->update(['active_channels' => $device->cameras()->count()]);

        return $created;
    }
}
```

### 5. Fluxo de Cadastro via UI

```
┌─────────────────────────────────────────────────────────────┐
│                   ADICIONAR DISPOSITIVO                       │
│                                                               │
│  1. Tipo: [DVR ▼]                                            │
│  2. Fabricante: [Intelbras ▼]                                │
│  3. Modelo: [MHDX 3116]                                     │
│  4. IP/DDNS: [192.168.1.200]                                 │
│  5. Porta RTSP: [554]                                        │
│  6. Credenciais: [admin / ****]                              │
│  7. Total de Canais: [16]                                    │
│                                                               │
│  [Testar Conexao]  [Descobrir Canais]                        │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  Canais Descobertos:                                         │
│  ┌──┬─────────────┬──────────┬───────────────┐              │
│  │✓ │ Canal 1     │ Ativo    │ Entrada       │              │
│  │✓ │ Canal 2     │ Ativo    │ Estacionamento│              │
│  │✓ │ Canal 3     │ Ativo    │ Portaria      │              │
│  │  │ Canal 4     │ Inativo  │ —             │              │
│  │  │ ...         │          │               │              │
│  │✓ │ Canal 16    │ Ativo    │ Deposito      │              │
│  └──┴─────────────┴──────────┴───────────────┘              │
│                                                               │
│  [Adicionar Selecionados como Cameras]                       │
└─────────────────────────────────────────────────────────────┘
```

### 6. Health Check de Dispositivos

```php
// app/Services/Device/DeviceHealthCheck.php

class DeviceHealthCheck
{
    /**
     * Health check especifico para DVRs/NVRs.
     * Verifica o dispositivo e cada canal individualmente.
     */
    public function check(Device $device): DeviceHealthReport
    {
        // 1. Verificar dispositivo (TCP + HTTP)
        $deviceOnline = $this->checkDeviceConnectivity($device);

        if (!$deviceOnline) {
            return DeviceHealthReport::offline($device);
        }

        // 2. Verificar cada canal ativo
        $channelStatuses = [];
        foreach ($device->activeChannels as $camera) {
            $channelStatuses[$camera->channel_number] = $this->checkChannel($device, $camera);
        }

        // 3. Verificar espaco em disco do DVR (via ONVIF/API)
        $diskStatus = $this->checkDiskStatus($device);

        // 4. Verificar firmware (se disponivel)
        $firmwareInfo = $this->checkFirmware($device);

        return new DeviceHealthReport(
            device: $device,
            online: true,
            channels: $channelStatuses,
            disk: $diskStatus,
            firmware: $firmwareInfo,
            checked_at: now(),
        );
    }
}
```

### 7. Gerenciamento em Lote

```php
// app/Services/Device/BulkManager.php

class BulkManager
{
    /**
     * Operacoes em lote para todos os canais de um DVR.
     */
    public function enableAllChannels(Device $device): void
    {
        $device->cameras()->update(['is_active' => true]);
    }

    public function disableAllChannels(Device $device): void
    {
        $device->cameras()->update(['is_active' => false]);
    }

    public function updateCredentials(Device $device, string $username, string $password): void
    {
        // Atualizar device e todas as cameras vinculadas
        $device->update([
            'username' => $username,
            'password' => encrypt($password),
        ]);

        $device->cameras()->update([
            'username' => $username,
            'password' => encrypt($password),
        ]);
    }

    /**
     * Re-sincronizar canais: detectar novos, remover inativos.
     */
    public function resyncChannels(Device $device): SyncResult
    {
        $discovery = app(ChannelDiscovery::class);
        $result = $discovery->discover($device);

        $added = 0;
        $removed = 0;

        foreach ($result->channels as $channel) {
            $existing = $device->cameras()
                ->where('channel_number', $channel->number)
                ->first();

            if ($channel->active && !$existing) {
                // Canal novo detectado
                $discovery->autoCreateCameras($device, [$channel]);
                $added++;
            } elseif (!$channel->active && $existing) {
                // Canal removido
                $existing->update(['is_active' => false]);
                $removed++;
            }
        }

        return new SyncResult($added, $removed);
    }
}
```

---

## Quota e Licenciamento

### Contagem de Cameras

Um DVR com 16 canais ativos conta como **16 cameras** para efeito de quota do plano:

```php
// app/Models/Tenant.php — Metodo existente

public function getCameraCount(): int
{
    // Conta cameras ativas (diretas + canais de DVR)
    return $this->cameras()->where('is_active', true)->count();
}
```

### Limites

| Plano | Cameras (Canais) | DVRs/NVRs |
|-------|-------------------|-----------|
| Starter | 16 | Ilimitado |
| Professional | 64 | Ilimitado |
| Enterprise | 10.000 | Ilimitado |

> Nota: O limite e por **canal de video ativo**, nao por dispositivo fisico.

---

## Consequencias

### Positivas
- Compatibilidade com 90%+ das instalacoes CFTV existentes no Brasil
- Auto-discovery reduz tempo de configuracao drasticamente
- Gerenciamento centralizado de DVRs e cameras
- Operacoes em lote (credenciais, ativar/desativar)
- Migracoes facilitadas de sistemas legados

### Negativas / Trade-offs
- Complexidade adicional no modelo de dados (Device + Camera)
- Paths RTSP proprietarios por fabricante
- Nem todos os DVRs suportam ONVIF plenamente
- Discovery pode gerar carga no DVR

---

## Alternativas Rejeitadas

### Tratar DVR como Camera Unica
- Impossivel visualizar canais individualmente
- Sem granularidade para alertas e gravacoes
- Inviavel para o usuario final

### Apenas Cameras IP Diretas
- Exclui 70%+ do mercado brasileiro de CFTV
- Ignora infraestrutura existente dos clientes
- Barreira de entrada muito alta

### Protocolo Proprietario por Fabricante (SDK)
- Custo de licenciamento proibitivo
- Manutenção de dezenas de SDKs
- Lock-in por fabricante

---

## ADRs Relacionadas

- ADR-0006 — Pipeline de Video (RTSP, HLS, WebRTC)
- ADR-0029 — Suporte a IPv6 e DDNS
- ADR-0031 — Compatibilidade Multi-Fabricante

---

## Referencias

- ONVIF Core Specification v22.06
- RTSP RFC 7826
- Intelbras API Documentation
- Hikvision ISAPI Reference
- Dahua SDK Documentation
