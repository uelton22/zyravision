# ADR-00022 — SLAs e Métricas de Performance

**Status:** Aprovado  
**Data:** 2026-01-22  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

O ZyraVision é uma plataforma de monitoramento CFTV de **missão crítica** que demanda:
- Alta disponibilidade (24/7/365)
- Baixa latência para operações em tempo real
- Performance consistente sob carga
- Métricas claras para operação e clientes

Sem SLAs e métricas bem definidos:
- Expectativas de clientes ficam indefinidas
- Problemas de performance passam despercebidos
- Priorização de incidentes é subjetiva
- Capacidade de planejamento é limitada

---

## Decisão

Estabelecer **SLAs (Service Level Agreements)**, **SLOs (Service Level Objectives)** e **SLIs (Service Level Indicators)** para todos os serviços críticos do ZyraVision.

---

## Definições

| Termo | Descrição |
|-------|-----------|
| **SLI** | Métrica que mede o comportamento do serviço |
| **SLO** | Meta interna para o SLI |
| **SLA** | Compromisso contratual com penalidades |

---

## SLOs por Serviço

### 1. API Principal (Laravel)

| SLI | SLO | Medição |
|-----|-----|---------|
| Disponibilidade | 99.9% | Uptime mensal |
| Latência p50 | < 100ms | Response time |
| Latência p95 | < 500ms | Response time |
| Latência p99 | < 1000ms | Response time |
| Error rate | < 0.1% | 5xx responses |

**Budget de Error:** 43 minutos de downtime por mês

### 2. Streaming de Vídeo

| SLI | SLO | Medição |
|-----|-----|---------|
| Disponibilidade do stream | 99.5% | Streams ativos |
| Tempo de início (TTFB) | < 3s | Primeiro frame |
| Buffering ratio | < 2% | Rebuffering events |
| Latência HLS | < 30s | Delay do live |
| Latência LL-HLS | < 5s | Delay do live |
| Latência WebRTC | < 1s | Delay do live |

### 3. Gravação

| SLI | SLO | Medição |
|-----|-----|---------|
| Disponibilidade de gravação | 99.9% | Gravações ativas |
| Gap de gravação | < 0.1% | Segundos perdidos |
| Integridade de arquivo | 100% | Checksum válido |
| Tempo de playback start | < 2s | Seek + play |

### 4. Armazenamento

| SLI | SLO | Medição |
|-----|-----|---------|
| Disponibilidade | 99.99% | Uptime |
| Durabilidade | 99.999999999% | Perda de dados |
| Throughput de escrita | > 100 MB/s | Por node |
| Throughput de leitura | > 200 MB/s | Por node |

### 5. Notificações (Push)

| SLI | SLO | Medição |
|-----|-----|---------|
| Taxa de entrega | > 99.5% | Delivered/sent |
| Latência P1 (crítico) | < 5s | Time to deliver |
| Latência P2 (alto) | < 30s | Time to deliver |
| Latência P3 (médio) | < 5min | Time to deliver |

### 6. IA/Analytics

| SLI | SLO | Medição |
|-----|-----|---------|
| Disponibilidade | 99% | Uptime |
| Latência de detecção | < 1s | Frame to event |
| Precisão (detecção pessoa) | > 95% | True positives |
| False positive rate | < 5% | False detections |

---

## SLOs por Endpoint

### Endpoints Críticos

| Endpoint | p50 | p95 | p99 | Error Rate |
|----------|-----|-----|-----|------------|
| `GET /api/v1/cameras` | 50ms | 200ms | 500ms | < 0.01% |
| `GET /api/v1/cameras/:id` | 30ms | 100ms | 300ms | < 0.01% |
| `POST /api/v1/cameras` | 100ms | 300ms | 800ms | < 0.1% |
| `GET /api/v1/streams/:id/url` | 50ms | 150ms | 400ms | < 0.01% |
| `GET /api/v1/recordings` | 100ms | 400ms | 1000ms | < 0.1% |
| `POST /api/v1/auth/login` | 100ms | 300ms | 800ms | < 0.1% |

### Endpoints de Alta Carga

| Endpoint | Rate Limit | Burst |
|----------|------------|-------|
| `GET /api/v1/cameras` | 100/min | 150 |
| `POST /api/v1/events` | 1000/min | 1500 |
| `GET /api/v1/streams/:id/url` | 60/min | 100 |

---

## SLAs por Tier de Cliente

### Tier Enterprise

| Métrica | SLA | Penalidade |
|---------|-----|------------|
| Disponibilidade | 99.9% | 10% crédito por 0.1% abaixo |
| Suporte (P1) | Resposta < 15min | 5% crédito |
| Suporte (P2) | Resposta < 1h | 2% crédito |
| Suporte (P3) | Resposta < 4h | - |

### Tier Professional

| Métrica | SLA | Penalidade |
|---------|-----|------------|
| Disponibilidade | 99.5% | 5% crédito por 0.5% abaixo |
| Suporte (P1) | Resposta < 1h | 3% crédito |
| Suporte (P2) | Resposta < 4h | - |
| Suporte (P3) | Resposta < 24h | - |

### Tier Starter

| Métrica | SLA | Penalidade |
|---------|-----|------------|
| Disponibilidade | 99% | Best effort |
| Suporte | Resposta < 48h | - |

---

## Métricas de Capacidade

### Por Câmera

| Recurso | Consumo Médio | Máximo |
|---------|---------------|--------|
| CPU (ingestão) | 5% core | 15% core |
| RAM (buffer) | 50 MB | 200 MB |
| Banda (entrada) | 4 Mbps | 8 Mbps |
| Storage (24h) | 43 GB | 86 GB |

### Por Tenant

| Limite | Starter | Professional | Enterprise |
|--------|---------|--------------|------------|
| Câmeras | 10 | 100 | Ilimitado |
| Storage | 100 GB | 1 TB | Custom |
| Retenção | 7 dias | 30 dias | Custom |
| Streams simultâneos | 5 | 50 | Custom |
| Users | 3 | 20 | Ilimitado |

---

## Monitoramento

### Stack de Observabilidade

```
┌─────────────────────────────────────────────────────────────┐
│                      Prometheus                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │ App Metrics │  │Infra Metrics│  │Custom Metrics│          │
│  └─────────────┘  └─────────────┘  └─────────────┘          │
├─────────────────────────────────────────────────────────────┤
│                        Grafana                               │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │ SLO Dashboard│ │ API Dashboard│ │Stream Dashboard         │
│  └─────────────┘  └─────────────┘  └─────────────┘          │
├─────────────────────────────────────────────────────────────┤
│                      Alertmanager                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │  PagerDuty  │  │    Slack    │  │    Email    │          │
│  └─────────────┘  └─────────────┘  └─────────────┘          │
└─────────────────────────────────────────────────────────────┘
```

### Queries Prometheus

```promql
# Disponibilidade da API
(1 - (
  sum(rate(http_requests_total{status=~"5.."}[5m]))
  /
  sum(rate(http_requests_total[5m]))
)) * 100

# Latência p95
histogram_quantile(0.95, 
  sum(rate(http_request_duration_seconds_bucket[5m])) by (le)
)

# Error budget restante (mensal)
1 - (
  sum(increase(http_requests_total{status=~"5.."}[30d]))
  /
  sum(increase(http_requests_total[30d]))
) / (1 - 0.999)

# Streams ativos vs esperados
sum(stream_active) / sum(camera_enabled) * 100
```

### Alertas

```yaml
# alertmanager/rules/slo.yml
groups:
  - name: slo-alerts
    rules:
      - alert: APIAvailabilityBelowSLO
        expr: |
          (1 - sum(rate(http_requests_total{status=~"5.."}[5m])) 
           / sum(rate(http_requests_total[5m]))) < 0.999
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "API availability below 99.9%"

      - alert: APILatencyAboveSLO
        expr: |
          histogram_quantile(0.95, 
            sum(rate(http_request_duration_seconds_bucket[5m])) by (le)
          ) > 0.5
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "API p95 latency above 500ms"

      - alert: ErrorBudgetBurning
        expr: |
          sum(rate(http_requests_total{status=~"5.."}[1h])) 
          / sum(rate(http_requests_total[1h])) > 0.01
        for: 15m
        labels:
          severity: critical
        annotations:
          summary: "Error budget burning fast (>1% error rate)"

      - alert: StreamAvailabilityLow
        expr: |
          sum(stream_active) / sum(camera_enabled) < 0.995
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Stream availability below 99.5%"
```

---

## Dashboard SLO

### Painéis Obrigatórios

1. **Overview**
   - Current availability (%)
   - Error budget remaining (%)
   - Active incidents

2. **API Performance**
   - Latency heatmap
   - Error rate by endpoint
   - Request rate

3. **Streaming Health**
   - Active streams
   - Buffering events
   - TTFB distribution

4. **Storage**
   - Write throughput
   - Read throughput
   - Capacity remaining

5. **SLO Burn Rate**
   - 1h burn rate
   - 6h burn rate
   - 24h trend

---

## Error Budget

### Cálculo

```
Error Budget (minutos/mês) = (1 - SLO) × minutos_no_mês

Para 99.9% SLO:
Error Budget = (1 - 0.999) × 43200 = 43.2 minutos/mês
```

### Política de Consumo

| Consumo do Budget | Ação |
|-------------------|------|
| 0-25% | Normal operation |
| 25-50% | Increased monitoring |
| 50-75% | Feature freeze, focus on reliability |
| 75-100% | Emergency fixes only |
| >100% | Incident declared |

---

## Relatórios

### Frequência

| Relatório | Frequência | Audiência |
|-----------|------------|-----------|
| SLO Status | Diário (automático) | Eng team |
| Availability Report | Semanal | Management |
| SLA Compliance | Mensal | Customers |
| Capacity Planning | Trimestral | Planning |

### Formato SLA Report

```markdown
# ZyraVision SLA Report - Janeiro 2026

## Executive Summary
- Overall Availability: 99.95%
- SLA Status: ✅ Met
- Major Incidents: 1

## Availability by Service
| Service | SLA | Actual | Status |
|---------|-----|--------|--------|
| API | 99.9% | 99.95% | ✅ |
| Streaming | 99.5% | 99.7% | ✅ |
| Storage | 99.99% | 100% | ✅ |

## Incidents
| Date | Duration | Impact | Root Cause |
|------|----------|--------|------------|
| 2026-01-15 | 22 min | API degraded | Database connection pool |

## Credits Applied: $0

## Recommendations
- Increase database connection pool size
- Add redundant PostgreSQL replica
```

---

## Consequências

### Positivas
- Expectativas claras com clientes
- Priorização objetiva de incidentes
- Planejamento de capacidade baseado em dados
- Cultura de confiabilidade

### Negativas / Trade-offs
- Overhead de monitoramento
- Potencial rigidez em SLAs
- Custo de créditos quando SLA não é atendido

---

## Alternativas Rejeitadas

### SLAs Únicos para Todos
- ❌ Não reflete diferentes necessidades
- ❌ Over-engineering para clientes menores

### Sem SLAs Formais
- ❌ Expectativas não gerenciadas
- ❌ Priorização subjetiva

---

## Referências

- Google SRE Book - Service Level Objectives
- Datadog SLO Management
- PagerDuty SLO Best Practices
- NIST Cloud Metrics Guide
