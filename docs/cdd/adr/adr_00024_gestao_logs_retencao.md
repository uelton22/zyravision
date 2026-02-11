# ADR-00024 — Gestão de Logs e Retenção

**Status:** Aprovado  
**Data:** 2026-01-22  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

O ZyraVision gera múltiplos tipos de logs:
- Logs de aplicação (Laravel)
- Logs de acesso a vídeo
- Logs de autenticação
- Logs de streaming (FFmpeg)
- Logs de infraestrutura (containers, sistema)
- Logs de auditoria (compliance)

Uma gestão inadequada de logs resulta em:
- Dificuldade de troubleshooting
- Não conformidade com LGPD/regulatórios
- Custos excessivos de storage
- Impossibilidade de forensics

---

## Decisão

Implementar uma **estratégia centralizada de logging** com:
- Logs estruturados (JSON)
- Centralização em stack ELK/OpenSearch
- Políticas de retenção por tipo
- Imutabilidade para logs de auditoria
- Compliance com LGPD

---

## Taxonomia de Logs

### Categorias e Retenção

| Categoria | Descrição | Retenção | Compliance |
|-----------|-----------|----------|------------|
| **Application** | Erros, warnings, debug | 30 dias | Não |
| **Access** | Requisições HTTP | 90 dias | Sim |
| **Auth** | Login, logout, token | 1 ano | Sim (LGPD) |
| **Audit** | Ações críticas | 5 anos | Sim (Legal) |
| **Video Access** | Quem viu qual vídeo | 1 ano | Sim (LGPD) |
| **Stream** | FFmpeg, transcoding | 7 dias | Não |
| **Infrastructure** | Containers, k8s | 30 dias | Não |

---

## Estrutura de Logs

### Formato Padrão (JSON)

```json
{
  "timestamp": "2026-01-22T10:30:00.123Z",
  "level": "info",
  "logger": "app",
  "message": "Camera stream started",
  "context": {
    "tenant_id": "tenant_001",
    "user_id": "user_123",
    "camera_id": "cam_456",
    "request_id": "req_789abc",
    "ip": "192.168.1.100",
    "user_agent": "ZyraVision-Flutter/1.0.0"
  },
  "metadata": {
    "service": "zyra-api",
    "version": "1.2.3",
    "host": "api-server-01",
    "environment": "production"
  }
}
```

### Campos Obrigatórios

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `timestamp` | ISO 8601 | Timestamp UTC |
| `level` | string | debug, info, warning, error, critical |
| `logger` | string | Categoria do log |
| `message` | string | Mensagem legível |
| `context.tenant_id` | string | ID do tenant |
| `context.request_id` | string | ID único da requisição |
| `metadata.service` | string | Nome do serviço |

---

## Implementação Laravel

### Configuração

```php
// config/logging.php
<?php

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'elasticsearch'],
        ],
        
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/zyravision.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        
        'elasticsearch' => [
            'driver' => 'custom',
            'via' => App\Logging\ElasticsearchLogger::class,
            'level' => 'info',
        ],
        
        'audit' => [
            'driver' => 'custom',
            'via' => App\Logging\AuditLogger::class,
            'level' => 'info',
        ],
    ],
];
```

### Logger Customizado

```php
// app/Logging/ElasticsearchLogger.php
<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\ElasticsearchHandler;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchLogger
{
    public function __invoke(array $config): Logger
    {
        $client = ClientBuilder::create()
            ->setHosts([config('services.elasticsearch.host')])
            ->build();
            
        $handler = new ElasticsearchHandler($client, [
            'index' => 'zyravision-logs',
            'type' => '_doc',
        ]);
        
        return new Logger('elasticsearch', [$handler]);
    }
}
```

### Middleware de Request Logging

```php
// app/Http/Middleware/RequestLogging.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RequestLogging
{
    public function handle($request, Closure $next)
    {
        $requestId = Str::uuid()->toString();
        $request->headers->set('X-Request-Id', $requestId);
        
        Log::channel('access')->info('Request received', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tenant_id' => $request->user()?->tenant_id,
            'user_id' => $request->user()?->id,
        ]);
        
        $response = $next($request);
        
        Log::channel('access')->info('Response sent', [
            'request_id' => $requestId,
            'status' => $response->status(),
            'duration_ms' => (microtime(true) - LARAVEL_START) * 1000,
        ]);
        
        $response->headers->set('X-Request-Id', $requestId);
        
        return $response;
    }
}
```

---

## Logs de Auditoria

### Eventos Auditáveis

| Evento | Criticidade | Dados Registrados |
|--------|-------------|-------------------|
| User login | Alta | user_id, ip, device, success |
| User logout | Média | user_id, session_duration |
| Password change | Alta | user_id, ip |
| Camera accessed | Alta | user_id, camera_id, duration |
| Recording viewed | Alta | user_id, recording_id, timestamp |
| Recording exported | Crítica | user_id, recording_id, hash |
| Config changed | Alta | user_id, config_key, old/new values |
| User created | Alta | admin_id, new_user_id |
| Permission changed | Alta | admin_id, user_id, changes |

### Modelo de Auditoria

```php
// app/Models/AuditLog.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'tenant_id',
        'user_id',
        'event_type',
        'resource_type',
        'resource_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'metadata',
        'created_at',
    ];
    
    protected $casts = [
        'old_values' => 'encrypted:array',
        'new_values' => 'encrypted:array',
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
    ];
}
```

### Service de Auditoria

```php
// app/Services/AuditService.php
<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(
        string $eventType,
        string $resourceType,
        ?string $resourceId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): AuditLog {
        return AuditLog::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'event_type' => $eventType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
    
    public function logVideoAccess(string $cameraId, ?string $recordingId = null): void
    {
        $this->log('video_access', 'camera', $cameraId, metadata: [
            'recording_id' => $recordingId,
            'access_type' => $recordingId ? 'playback' : 'live',
        ]);
    }
}
```

---

## Arquitetura de Logging

### Stack Centralizada

```
┌─────────────────────────────────────────────────────────────────┐
│                        APLICAÇÕES                                │
├───────────────┬───────────────┬───────────────┬─────────────────┤
│   zyra-api    │  zyra-media   │   zyra-ai     │   zyra-web      │
│   (Laravel)   │   (FFmpeg)    │   (Python)    │   (Vue)         │
└───────┬───────┴───────┬───────┴───────┬───────┴────────┬────────┘
        │               │               │                │
        ▼               ▼               ▼                ▼
┌─────────────────────────────────────────────────────────────────┐
│                       FLUENTD / FLUENT BIT                       │
│            (Collection, Parsing, Enrichment)                     │
└───────────────────────────────┬─────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                    OPENSEARCH / ELASTICSEARCH                    │
│                     (Indexing, Storage)                          │
├─────────────────────────────────────────────────────────────────┤
│   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐          │
│   │application- │   │   audit-    │   │  access-    │          │
│   │   logs      │   │    logs     │   │   logs      │          │
│   └─────────────┘   └─────────────┘   └─────────────┘          │
└───────────────────────────────┬─────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                  OPENSEARCH DASHBOARDS / KIBANA                  │
│                 (Visualization, Alerting)                        │
└─────────────────────────────────────────────────────────────────┘
```

### Fluent Bit Configuration

```yaml
# fluent-bit.conf
[SERVICE]
    Flush        5
    Daemon       Off
    Log_Level    info

[INPUT]
    Name         tail
    Path         /var/log/zyravision/*.log
    Parser       json
    Tag          zyra.*

[FILTER]
    Name         record_modifier
    Match        *
    Record       cluster ${CLUSTER_NAME}
    Record       namespace ${POD_NAMESPACE}

[OUTPUT]
    Name         opensearch
    Match        *
    Host         opensearch.logging.svc
    Port         9200
    Index        zyravision-logs
    Logstash_Format On
    Logstash_Prefix zyravision
```

---

## Políticas de Retenção

### Index Lifecycle Management (ILM)

```json
{
  "policy": {
    "phases": {
      "hot": {
        "min_age": "0ms",
        "actions": {
          "rollover": {
            "max_size": "50gb",
            "max_age": "1d"
          }
        }
      },
      "warm": {
        "min_age": "7d",
        "actions": {
          "shrink": { "number_of_shards": 1 },
          "forcemerge": { "max_num_segments": 1 }
        }
      },
      "cold": {
        "min_age": "30d",
        "actions": {
          "freeze": {}
        }
      },
      "delete": {
        "min_age": "90d",
        "actions": {
          "delete": {}
        }
      }
    }
  }
}
```

### Política por Tipo de Log

```yaml
retention_policies:
  application:
    hot: 7d
    warm: 23d
    delete: 30d
    
  access:
    hot: 7d
    warm: 30d
    cold: 53d
    delete: 90d
    
  audit:
    hot: 30d
    warm: 335d
    cold: 4y
    delete: 5y
    immutable: true
    
  video_access:
    hot: 30d
    warm: 335d
    delete: 1y
    immutable: true
```

---

## Compliance LGPD

### Requisitos

1. **Direito ao Esquecimento**
   - Logs de auditoria podem reter dados anonimizados
   - Logs de acesso devem ser expurgáveis

2. **Minimização de Dados**
   - Não logar dados sensíveis desnecessários
   - Mascarar/hash dados pessoais quando possível

3. **Acesso Controlado**
   - Apenas admins acessam logs
   - Logs de auditoria separados

### Anonimização

```php
// app/Services/LogAnonymizer.php
<?php

class LogAnonymizer
{
    public function anonymize(array $logData): array
    {
        if (isset($logData['context']['ip'])) {
            $logData['context']['ip'] = $this->hashIp($logData['context']['ip']);
        }
        
        if (isset($logData['context']['email'])) {
            $logData['context']['email'] = '[REDACTED]';
        }
        
        return $logData;
    }
    
    private function hashIp(string $ip): string
    {
        return hash('sha256', $ip . config('app.key'));
    }
}
```

---

## Alertas

### Regras de Alerta

```yaml
# opensearch-alerting/rules.yml
rules:
  - name: High Error Rate
    condition: 
      query: 'level:error'
      threshold: 100
      period: 5m
    severity: warning
    channels: [slack, email]
    
  - name: Critical Error
    condition:
      query: 'level:critical'
      threshold: 1
      period: 1m
    severity: critical
    channels: [pagerduty, slack]
    
  - name: Suspicious Auth Activity
    condition:
      query: 'event_type:login AND success:false'
      threshold: 10
      period: 5m
      group_by: ip_address
    severity: high
    channels: [security-team, slack]
```

---

## Consequências

### Positivas
- Troubleshooting eficiente
- Conformidade com LGPD
- Forensics completo
- Custos controlados

### Negativas / Trade-offs
- Infraestrutura adicional (ELK stack)
- Overhead de processamento
- Complexidade de gerenciamento

---

## Alternativas Rejeitadas

### Logs em Arquivos Locais
- ❌ Não centralizado
- ❌ Difícil pesquisar
- ❌ Perda de dados

### Cloud Logging Proprietário
- ❌ Vendor lock-in
- ❌ Custo elevado em escala

---

## Referências

- ELK Stack Documentation
- OpenSearch Best Practices
- LGPD Article 18 (Direitos do Titular)
- OWASP Logging Cheat Sheet
