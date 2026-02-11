# ADR-00025 — Integrações com Sistemas Externos

**Status:** Aprovado  
**Data:** 2026-01-22  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

O ZyraVision opera em ambientes corporativos que possuem sistemas existentes:
- ERPs (SAP, TOTVS, Oracle)
- CRMs (Salesforce, HubSpot)
- SIEMs (Splunk, Wazuh, Elastic SIEM)
- Controle de Acesso (Catracas, Portões)
- Alarmes (Centrais de monitoramento)
- Automação Predial (BMS)
- Help Desk (ServiceNow, Zendesk)

A integração com esses sistemas é **crítica** para:
- Correlação de eventos de segurança
- Automação de workflows
- Unificação de dados
- Resposta coordenada a incidentes

---

## Decisão

Implementar uma **arquitetura de integrações extensível** baseada em:
- Webhooks para notificações outbound
- APIs REST para consultas
- Message brokers para eventos
- Conectores pré-construídos para sistemas comuns

---

## Arquitetura de Integrações

### Visão Geral

```
┌─────────────────────────────────────────────────────────────────┐
│                        ZYRAVISION CORE                           │
├─────────────────────────────────────────────────────────────────┤
│                      Integration Layer                           │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │ Webhooks │  │ Message  │  │   REST   │  │Connectors│        │
│  │  Engine  │  │  Broker  │  │   APIs   │  │  Engine  │        │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘        │
└───────┼─────────────┼─────────────┼─────────────┼───────────────┘
        │             │             │             │
        ▼             ▼             ▼             ▼
┌───────────────────────────────────────────────────────────────────┐
│                     EXTERNAL SYSTEMS                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐          │
│  │   SIEM   │  │   ERP    │  │ Access   │  │  Alarm   │          │
│  │(Splunk)  │  │(SAP/TOTVS│  │ Control  │  │ Central  │          │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘          │
└───────────────────────────────────────────────────────────────────┘
```

---

## 1. Webhooks (Outbound)

### Eventos Disponíveis

| Evento | Payload | Uso Típico |
|--------|---------|------------|
| `camera.offline` | camera_id, timestamp | Alertas SIEM |
| `camera.online` | camera_id, timestamp | Automação |
| `motion.detected` | camera_id, timestamp, zone | Alarmes |
| `person.detected` | camera_id, timestamp, confidence | Segurança |
| `face.recognized` | camera_id, person_id, confidence | Acesso |
| `plate.recognized` | camera_id, plate, confidence | Portaria |
| `recording.started` | camera_id, timestamp | Auditoria |
| `recording.stopped` | camera_id, timestamp, duration | Auditoria |
| `storage.threshold` | tenant_id, usage_percent | ERP/Billing |
| `user.login` | user_id, ip, device | SIEM |

### Configuração de Webhook

```json
{
  "id": "wh_001",
  "name": "SIEM Integration",
  "url": "https://siem.company.com/api/events",
  "secret": "whsec_xxxxx",
  "events": ["camera.offline", "person.detected", "user.login"],
  "headers": {
    "Authorization": "Bearer ${SIEM_TOKEN}"
  },
  "retry_policy": {
    "max_attempts": 5,
    "backoff": "exponential"
  },
  "filters": {
    "tenant_id": "tenant_001",
    "severity": ["high", "critical"]
  }
}
```

### Implementação

```php
// app/Services/WebhookService.php
<?php

namespace App\Services;

use App\Models\Webhook;
use App\Jobs\SendWebhookJob;
use Illuminate\Support\Facades\Http;

class WebhookService
{
    public function dispatch(string $event, array $payload): void
    {
        $webhooks = Webhook::where('active', true)
            ->whereJsonContains('events', $event)
            ->get();
            
        foreach ($webhooks as $webhook) {
            if ($this->matchesFilters($webhook, $payload)) {
                SendWebhookJob::dispatch($webhook, $event, $payload);
            }
        }
    }
    
    public function send(Webhook $webhook, string $event, array $payload): bool
    {
        $signature = hash_hmac('sha256', 
            json_encode($payload), 
            $webhook->secret
        );
        
        $response = Http::withHeaders([
            'X-Zyra-Event' => $event,
            'X-Zyra-Signature' => $signature,
            'X-Zyra-Timestamp' => now()->timestamp,
            ...$webhook->headers,
        ])
        ->timeout(30)
        ->post($webhook->url, $payload);
        
        return $response->successful();
    }
}
```

### Payload Padrão

```json
{
  "id": "evt_123456",
  "event": "person.detected",
  "timestamp": "2026-01-22T10:30:00Z",
  "tenant_id": "tenant_001",
  "data": {
    "camera_id": "cam_456",
    "camera_name": "Entrada Principal",
    "confidence": 0.95,
    "bounding_box": { "x": 100, "y": 200, "w": 50, "h": 120 },
    "snapshot_url": "https://api.zyravision.com/snapshots/snap_789.jpg"
  }
}
```

---

## 2. Message Broker (Events)

### RabbitMQ / Redis Streams

Para integrações de alto volume ou baixa latência.

```yaml
# docker-compose.yml
services:
  rabbitmq:
    image: rabbitmq:3-management
    ports:
      - "5672:5672"
      - "15672:15672"
    environment:
      RABBITMQ_DEFAULT_USER: zyra
      RABBITMQ_DEFAULT_PASS: ${RABBITMQ_PASSWORD}
```

### Exchanges e Queues

```
Exchange: zyra.events (topic)
├── Queue: siem.events
│   Binding: *.detected, user.*, camera.offline
│
├── Queue: alarm.events  
│   Binding: motion.detected, person.detected
│
└── Queue: erp.events
    Binding: storage.*, recording.*
```

### Producer

```php
// app/Events/CameraEvent.php
<?php

class CameraEvent implements ShouldBroadcast
{
    public function __construct(
        public string $eventType,
        public Camera $camera,
        public array $data = []
    ) {}
    
    public function broadcastOn(): array
    {
        return [
            new Channel('events'),
        ];
    }
    
    public function broadcastAs(): string
    {
        return $this->eventType;
    }
}
```

---

## 3. REST API para Integrações

### Endpoints de Integração

```yaml
openapi: 3.0.0
paths:
  /api/v1/integrations/cameras:
    get:
      summary: Lista câmeras para sistemas externos
      security:
        - ApiKeyAuth: []
      responses:
        200:
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/CameraList'

  /api/v1/integrations/events:
    get:
      summary: Consulta eventos recentes
      parameters:
        - name: since
          in: query
          schema:
            type: string
            format: date-time
        - name: types
          in: query
          schema:
            type: array
            items:
              type: string
      responses:
        200:
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/EventList'

  /api/v1/integrations/snapshots/{camera_id}:
    get:
      summary: Obtém snapshot atual da câmera
      responses:
        200:
          content:
            image/jpeg:
              schema:
                type: string
                format: binary
```

### Autenticação de Integrações

```php
// app/Http/Middleware/ApiKeyAuth.php
<?php

class ApiKeyAuth
{
    public function handle($request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey) {
            return response()->json(['error' => 'API key required'], 401);
        }
        
        $integration = Integration::where('api_key', hash('sha256', $apiKey))
            ->where('active', true)
            ->first();
            
        if (!$integration) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }
        
        // Rate limiting por integração
        if (RateLimiter::tooManyAttempts($integration->id, $integration->rate_limit)) {
            return response()->json(['error' => 'Rate limit exceeded'], 429);
        }
        
        RateLimiter::hit($integration->id);
        
        $request->merge(['integration' => $integration]);
        
        return $next($request);
    }
}
```

---

## 4. Conectores Pré-Construídos

### SIEM Integrations

#### Splunk

```php
// app/Integrations/Splunk/SplunkConnector.php
<?php

class SplunkConnector extends BaseConnector
{
    public function sendEvent(array $event): bool
    {
        return Http::withHeaders([
            'Authorization' => "Splunk {$this->config['hec_token']}",
        ])
        ->post("{$this->config['host']}/services/collector/event", [
            'event' => $event,
            'sourcetype' => 'zyravision',
            'index' => $this->config['index'] ?? 'main',
        ])
        ->successful();
    }
}
```

#### Wazuh

```php
// app/Integrations/Wazuh/WazuhConnector.php
<?php

class WazuhConnector extends BaseConnector
{
    public function sendAlert(array $alert): bool
    {
        return Http::withBasicAuth(
            $this->config['username'],
            $this->config['password']
        )
        ->post("{$this->config['host']}/alerts", [
            'rule' => [
                'level' => $alert['severity'],
                'description' => $alert['message'],
            ],
            'data' => $alert['data'],
        ])
        ->successful();
    }
}
```

### Access Control Integrations

#### Generic Access Control

```php
// app/Integrations/AccessControl/AccessControlConnector.php
<?php

class AccessControlConnector extends BaseConnector
{
    public function grantAccess(string $personId, string $gateId): bool
    {
        return Http::post("{$this->config['host']}/api/access/grant", [
            'person_id' => $personId,
            'gate_id' => $gateId,
            'timestamp' => now()->toIso8601String(),
        ])->successful();
    }
    
    public function onFaceRecognized(array $event): void
    {
        $person = Person::find($event['person_id']);
        
        if ($person && $person->hasAccessTo($event['zone'])) {
            $this->grantAccess($person->id, $event['zone']);
        }
    }
}
```

### ERP/Billing Integrations

```php
// app/Integrations/TOTVS/TOTVSConnector.php
<?php

class TOTVSConnector extends BaseConnector
{
    public function syncUsage(Tenant $tenant): bool
    {
        $usage = [
            'tenant_code' => $tenant->erp_code,
            'cameras_count' => $tenant->cameras()->count(),
            'storage_gb' => $tenant->storage_used_gb,
            'period' => now()->format('Y-m'),
        ];
        
        return Http::withToken($this->config['token'])
            ->post("{$this->config['host']}/api/billing/usage", $usage)
            ->successful();
    }
}
```

---

## 5. Automações e Workflows

### Trigger-Action Engine

```yaml
# config/automations/person_detected_gate.yml
automation:
  name: "Open gate on recognized face"
  trigger:
    event: face.recognized
    conditions:
      - confidence: ">= 0.90"
      - person.has_access: true
      - camera.zone: "entrance"
  actions:
    - type: access_control
      action: grant_access
      params:
        gate_id: "${camera.linked_gate}"
        duration: 30
    - type: log
      action: audit
      params:
        message: "Access granted to ${person.name}"
```

### Workflow Engine

```php
// app/Services/AutomationEngine.php
<?php

class AutomationEngine
{
    public function process(string $eventType, array $payload): void
    {
        $automations = Automation::where('trigger_event', $eventType)
            ->where('active', true)
            ->get();
            
        foreach ($automations as $automation) {
            if ($this->matchesConditions($automation, $payload)) {
                foreach ($automation->actions as $action) {
                    $this->executeAction($action, $payload);
                }
            }
        }
    }
    
    private function executeAction(array $action, array $context): void
    {
        $handler = $this->getActionHandler($action['type']);
        $params = $this->interpolateParams($action['params'], $context);
        
        $handler->execute($action['action'], $params);
    }
}
```

---

## 6. Configuração de Integrações

### UI de Gerenciamento

```typescript
// Frontend: IntegrationConfig.vue
interface Integration {
  id: string;
  name: string;
  type: 'webhook' | 'siem' | 'access_control' | 'erp';
  config: Record<string, any>;
  events: string[];
  active: boolean;
  last_success_at: string | null;
  error_count: number;
}
```

### API de Gerenciamento

```yaml
paths:
  /api/v1/admin/integrations:
    get:
      summary: Lista integrações configuradas
    post:
      summary: Cria nova integração
      
  /api/v1/admin/integrations/{id}/test:
    post:
      summary: Testa integração
      
  /api/v1/admin/integrations/{id}/logs:
    get:
      summary: Logs de execução da integração
```

---

## Segurança

### Requisitos

| Aspecto | Implementação |
|---------|---------------|
| Autenticação | API Keys com hash SHA-256 |
| Autorização | Scopes por integração |
| Transporte | TLS 1.3 obrigatório |
| Secrets | Vault/KMS para credenciais |
| Auditoria | Log de todas as chamadas |
| Rate Limiting | Por integração |

### Modelo de Permissões

```php
// app/Models/Integration.php
protected $casts = [
    'scopes' => 'array',
    'allowed_events' => 'array',
    'ip_whitelist' => 'array',
];

// Scopes disponíveis
const SCOPES = [
    'cameras:read',
    'events:read',
    'events:write',
    'snapshots:read',
    'recordings:read',
    'access_control:write',
];
```

---

## Monitoramento

### Métricas

```promql
# Taxa de sucesso de webhooks
sum(rate(webhook_deliveries_success_total[5m])) 
/ sum(rate(webhook_deliveries_total[5m])) * 100

# Latência média de entrega
histogram_quantile(0.95, 
  sum(rate(webhook_delivery_duration_seconds_bucket[5m])) by (le)
)

# Integrações com erro
count(integration_last_error_timestamp > (time() - 3600))
```

### Alertas

```yaml
rules:
  - alert: IntegrationDeliveryFailed
    expr: |
      increase(webhook_deliveries_failed_total[15m]) > 10
    labels:
      severity: warning
      
  - alert: IntegrationDown  
    expr: |
      integration_up == 0
    for: 5m
    labels:
      severity: critical
```

---

## Consequências

### Positivas
- Ecossistema extensível
- Automação de workflows
- Integração com infraestrutura existente
- Valor agregado para clientes enterprise

### Negativas / Trade-offs
- Complexidade de manutenção
- Dependência de sistemas externos
- Debugging mais difícil

---

## Alternativas Rejeitadas

### Integrações Hardcoded
- ❌ Não escalável
- ❌ Requer deploy para mudanças

### iPaaS Externo (Zapier/Make)
- ❌ Custo adicional
- ❌ Latência
- ❌ Dependência externa

---

## Referências

- Stripe Webhooks Best Practices
- GitHub Webhooks Guide
- RabbitMQ Patterns
- Enterprise Integration Patterns
