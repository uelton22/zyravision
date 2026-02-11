# ADR-00021 — Estratégia de Testes e Qualidade

**Status:** Aprovado  
**Data:** 2026-01-22  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

O ZyraVision é uma plataforma de **missão crítica** que opera 24/7. Falhas no sistema podem resultar em:
- Perda de monitoramento de segurança
- Evidências não gravadas
- Violações de compliance
- Impacto financeiro e legal

Uma estratégia de testes robusta é **fundamental** para:
- Garantir qualidade do código
- Prevenir regressões
- Acelerar ciclos de desenvolvimento
- Aumentar confiança em deploys

---

## Decisão

Adotar uma **estratégia de testes em pirâmide** com múltiplas camadas, cobertura mínima obrigatória e automação completa.

---

## Pirâmide de Testes

```
                    ┌─────────────────┐
                    │      E2E        │  5%
                    │   (Playwright)  │
                    ├─────────────────┤
                    │   Integration   │  20%
                    │   (Pest/PHPUnit)│
                    ├─────────────────┤
                    │      Unit       │  75%
                    │   (Pest/PHPUnit)│
                    └─────────────────┘
```

---

## Camadas de Teste

### 1. Testes Unitários (75% do esforço)

**Framework:** Pest PHP (com suporte PHPUnit)

#### O que testar:
- Models e suas regras de negócio
- Services e use cases
- Validators e form requests
- Helpers e utilities
- Policies e gates

#### Cobertura Mínima por Módulo

| Módulo | Cobertura Mínima |
|--------|------------------|
| Domains/Auth | 90% |
| Domains/Camera | 85% |
| Domains/Stream | 85% |
| Domains/Storage | 80% |
| Domains/Tenant | 90% |
| Services | 85% |
| Models | 80% |

#### Exemplo de Teste Unitário

```php
<?php

use App\Domains\Camera\Services\CameraHealthService;
use App\Models\Camera;

describe('CameraHealthService', function () {
    
    it('marks camera as offline when stream is unreachable', function () {
        $camera = Camera::factory()->create(['status' => 'online']);
        $service = new CameraHealthService();
        
        $service->checkHealth($camera, reachable: false);
        
        expect($camera->fresh()->status)->toBe('offline');
    });
    
    it('calculates correct uptime percentage', function () {
        $camera = Camera::factory()
            ->hasHealthChecks(100, ['success' => true])
            ->hasHealthChecks(5, ['success' => false])
            ->create();
        
        $uptime = $service->calculateUptime($camera);
        
        expect($uptime)->toBe(95.24); // 100/105
    });
    
});
```

### 2. Testes de Integração (20% do esforço)

**Framework:** Pest PHP + Laravel Test Helpers

#### O que testar:
- Endpoints de API
- Fluxos de autenticação
- Pipelines de processamento
- Integrações entre serviços
- Jobs e queues

#### Exemplo de Teste de Integração

```php
<?php

use App\Models\User;
use App\Models\Camera;

describe('Camera API', function () {
    
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });
    
    it('lists cameras for authenticated user', function () {
        Camera::factory()->count(5)->create(['tenant_id' => $this->user->tenant_id]);
        Camera::factory()->count(3)->create(); // Outro tenant
        
        $response = $this->getJson('/api/v1/cameras');
        
        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'rtsp_url', 'status']
                ]
            ]);
    });
    
    it('validates camera creation payload', function () {
        $response = $this->postJson('/api/v1/cameras', [
            'name' => '', // inválido
            'rtsp_url' => 'not-a-url', // inválido
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'rtsp_url']);
    });
    
});
```

### 3. Testes End-to-End (5% do esforço)

**Framework:** Playwright

#### O que testar:
- Fluxos críticos de usuário
- Login/logout
- Visualização de câmeras
- Playback de gravações
- Fluxos de configuração

#### Exemplo de Teste E2E

```typescript
import { test, expect } from '@playwright/test';

test.describe('Camera Live View', () => {
  
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('[data-testid="email"]', 'operator@test.com');
    await page.fill('[data-testid="password"]', 'password');
    await page.click('[data-testid="login-button"]');
    await expect(page).toHaveURL('/dashboard');
  });

  test('should display live stream when clicking on camera', async ({ page }) => {
    await page.click('[data-testid="camera-card-001"]');
    
    const videoPlayer = page.locator('[data-testid="video-player"]');
    await expect(videoPlayer).toBeVisible();
    
    // Verifica se o stream está carregando
    await expect(page.locator('[data-testid="loading-indicator"]')).toBeHidden();
  });

  test('should show offline indicator for disconnected camera', async ({ page }) => {
    await page.goto('/cameras/offline-camera-id');
    
    await expect(page.locator('[data-testid="offline-badge"]')).toBeVisible();
    await expect(page.locator('[data-testid="video-player"]')).not.toBeVisible();
  });
  
});
```

---

## Testes Específicos do Domínio CFTV

### Testes de Streaming

```php
describe('FFmpeg Pipeline', function () {
    
    it('converts RTSP to HLS segments', function () {
        $pipeline = new FFmpegPipeline();
        
        $result = $pipeline->startTranscode(
            input: 'rtsp://camera.local/stream',
            output: '/tmp/test/stream.m3u8'
        );
        
        expect($result->isSuccessful())->toBeTrue();
        expect(file_exists('/tmp/test/stream.m3u8'))->toBeTrue();
    });
    
    it('handles stream disconnection gracefully', function () {
        $pipeline = new FFmpegPipeline();
        
        $result = $pipeline->startTranscode(
            input: 'rtsp://invalid.camera/stream',
            output: '/tmp/test/stream.m3u8'
        );
        
        expect($result->hasError())->toBeTrue();
        expect($result->errorType)->toBe('connection_failed');
    });
    
});
```

### Testes de Storage

```php
describe('Video Storage', function () {
    
    it('stores video segment with correct path structure', function () {
        $storage = new VideoStorageService();
        
        $segment = new VideoSegment(
            tenantId: 'tenant_001',
            cameraId: 'camera_001',
            timestamp: now(),
            data: fopen('fixtures/segment.ts', 'r')
        );
        
        $path = $storage->store($segment);
        
        expect($path)->toMatch('/tenant_001\/camera_001\/2026\/01\/22\//');
    });
    
    it('enforces quota limits', function () {
        $tenant = Tenant::factory()->create(['storage_quota_gb' => 1]);
        $storage = new VideoStorageService();
        
        // Simula storage cheio
        $tenant->update(['storage_used_bytes' => 1 * 1024 * 1024 * 1024]);
        
        expect(fn() => $storage->store($segment))
            ->toThrow(StorageQuotaExceededException::class);
    });
    
});
```

---

## Testes de Performance e Carga

### Ferramenta: k6

```javascript
// k6/camera-list.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '2m', target: 100 },  // Ramp up
    { duration: '5m', target: 100 },  // Sustain
    { duration: '2m', target: 200 },  // Spike
    { duration: '2m', target: 0 },    // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],   // 95% das requests < 500ms
    http_req_failed: ['rate<0.01'],     // < 1% de falhas
  },
};

export default function () {
  const res = http.get('https://api.zyravision.local/api/v1/cameras', {
    headers: { Authorization: `Bearer ${__ENV.API_TOKEN}` },
  });
  
  check(res, {
    'status is 200': (r) => r.status === 200,
    'response time < 500ms': (r) => r.timings.duration < 500,
  });
  
  sleep(1);
}
```

### Thresholds de Performance

| Endpoint | p50 | p95 | p99 |
|----------|-----|-----|-----|
| GET /cameras | 50ms | 200ms | 500ms |
| GET /cameras/:id | 30ms | 100ms | 300ms |
| POST /cameras | 100ms | 300ms | 800ms |
| GET /streams/:id/url | 50ms | 150ms | 400ms |
| GET /recordings | 100ms | 400ms | 1000ms |

---

## Testes de Contrato (API)

### OpenAPI Schema Validation

```php
describe('API Contract', function () {
    
    it('camera list response matches OpenAPI schema', function () {
        $response = $this->getJson('/api/v1/cameras');
        
        $validator = new OpenApiValidator('/docs/openapi.yaml');
        $result = $validator->validate('GET /api/v1/cameras', $response);
        
        expect($result->isValid())->toBeTrue();
    });
    
});
```

---

## CI/CD Integration

### Pipeline de Testes

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  unit-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: xdebug
      
      - run: composer install
      - run: php artisan test --parallel --coverage --min=80
      
      - uses: codecov/codecov-action@v3

  integration-tests:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_DB: zyravision_test
          POSTGRES_PASSWORD: test
      redis:
        image: redis:7
    
    steps:
      - uses: actions/checkout@v4
      - run: composer install
      - run: php artisan test --testsuite=Integration

  e2e-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
      - run: npm ci
      - run: npx playwright install
      - run: npx playwright test
      
      - uses: actions/upload-artifact@v4
        if: failure()
        with:
          name: playwright-report
          path: playwright-report/

  performance-tests:
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v4
      - uses: grafana/k6-action@v0.3.0
        with:
          filename: k6/camera-list.js
```

---

## Monitoramento Sintético

### Testes em Produção

```yaml
# Configuração Checkly / Synthetic Monitoring
checks:
  - name: API Health
    type: API
    frequency: 1m
    locations: [us-east-1, eu-west-1, sa-east-1]
    request:
      url: https://api.zyravision.com/health
    assertions:
      - source: status_code
        comparison: equals
        target: 200
      - source: response_time
        comparison: less_than
        target: 1000

  - name: Camera List
    type: API
    frequency: 5m
    request:
      url: https://api.zyravision.com/api/v1/cameras
      headers:
        Authorization: Bearer ${SYNTHETIC_TOKEN}
    assertions:
      - source: status_code
        comparison: equals
        target: 200
```

---

## Métricas de Qualidade

### Obrigatórias

| Métrica | Mínimo | Target |
|---------|--------|--------|
| Cobertura de código | 80% | 90% |
| Taxa de sucesso de testes | 100% | 100% |
| Tempo de execução CI | < 15min | < 10min |
| Flaky tests | 0% | 0% |

### Dashboard

```promql
# Taxa de sucesso de builds
sum(ci_build_success_total) / sum(ci_build_total) * 100

# Tempo médio de execução de testes
avg(ci_test_duration_seconds)
```

---

## Consequências

### Positivas
- Alta confiança em releases
- Rápida detecção de regressões
- Documentação viva através de testes
- Facilita refatorações

### Negativas / Trade-offs
- Tempo inicial de desenvolvimento maior
- Manutenção contínua de testes
- Infraestrutura de CI/CD

---

## Alternativas Rejeitadas

### Apenas Testes Manuais
- ❌ Não escala
- ❌ Propenso a erros
- ❌ Lento

### Apenas Testes E2E
- ❌ Muito lentos
- ❌ Difícil isolar problemas
- ❌ Alto custo de manutenção

---

## Referências

- Pest PHP Documentation
- Playwright Documentation
- k6 Load Testing Guide
- Martin Fowler - Testing Pyramid
- Google Testing Blog
