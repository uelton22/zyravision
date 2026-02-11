# ADR-00014 — Push Notifications e Alertas Críticos do ZyraVision

**Status:** Aprovado  
**Data:** 2026-01-21  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

Em sistemas CFTV profissionais, eventos críticos precisam alcançar operadores **imediatamente**, independentemente de estarem ativamente usando o sistema. Atrasos em notificações podem resultar em:

- Falhas de segurança
- Perda de evidências
- Tempo de resposta inadequado a incidentes
- Danos materiais ou pessoais

Grandes plataformas VMS (Genetec, Milestone, Verkada) investem pesadamente em sistemas de notificação confiáveis e configuráveis como diferencial competitivo.

---

## Decisão

Implementar um **sistema robusto de Push Notifications** utilizando:
- **Firebase Cloud Messaging (FCM)** para Android e Web
- **Apple Push Notification Service (APNs)** para iOS

O backend Laravel será responsável por:
- Gerenciar tokens de dispositivos
- Classificar e priorizar alertas
- Enviar notificações segmentadas
- Rastrear entregas e falhas

---

## Taxonomia de Alertas

### Níveis de Severidade

| Nível | Descrição | Ação | Exemplo |
|-------|-----------|------|---------|
| **P1 - Crítico** | Requer ação imediata | Push + Som + Vibração | Intrusão detectada |
| **P2 - Alto** | Importante, mas não urgente | Push + Som | Câmera offline |
| **P3 - Médio** | Informativo relevante | Push silencioso | Movimento detectado |
| **P4 - Baixo** | Informativo geral | In-app only | Backup concluído |

### Tipos de Eventos Notificáveis

#### Eventos de Infraestrutura
- Câmera offline/online
- Falha de gravação
- Storage cheio (>80%, >90%, 100%)
- Perda de conexão RTSP
- Falha de serviço

#### Eventos de IA
- Detecção de movimento
- Pessoa detectada
- Face reconhecida
- Placa identificada (LPR)
- Objeto abandonado

#### Eventos de Sistema
- Login suspeito
- Falha de autenticação
- Alteração de configuração crítica
- Exportação de vídeo

---

## Arquitetura Técnica

### Fluxo de Notificação

```
Evento → Laravel → Queue → Notification Service → FCM/APNs → Dispositivo
                                    ↓
                              Fallback (WebSocket/Email)
```

### Componentes

#### 1. Token Registry
- Armazena tokens por dispositivo
- Associa tokens a usuários e tenants
- Mantém histórico de atualizações

#### 2. Notification Engine
- Processa eventos em fila
- Aplica regras de roteamento
- Gerencia rate limiting

#### 3. Delivery Tracker
- Monitora entregas
- Registra falhas
- Retry automático

---

## Modelo de Dados

```sql
-- Tabela de dispositivos
CREATE TABLE user_devices (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL,
    tenant_id UUID NOT NULL,
    device_type VARCHAR(20), -- ios, android, web
    push_token TEXT,
    device_name VARCHAR(100),
    last_seen_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    revoked_at TIMESTAMP
);

-- Tabela de notificações
CREATE TABLE notifications (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL,
    tenant_id UUID NOT NULL,
    type VARCHAR(50),
    severity VARCHAR(10),
    title VARCHAR(200),
    body TEXT,
    data JSONB,
    read_at TIMESTAMP,
    created_at TIMESTAMP
);

-- Tabela de preferências
CREATE TABLE notification_preferences (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL,
    event_type VARCHAR(50),
    channel VARCHAR(20), -- push, email, sms, webhook
    enabled BOOLEAN DEFAULT TRUE,
    quiet_hours_start TIME,
    quiet_hours_end TIME
);
```

---

## Configurações por Usuário

### Opt-in/Opt-out Granular

Cada usuário pode configurar:
- **Tipos de evento** que deseja receber
- **Canais de entrega** (push, email, SMS)
- **Horários silenciosos** (quiet hours)
- **Câmeras específicas** para monitorar

### Exemplo de Configuração

```json
{
  "user_id": "uuid",
  "preferences": {
    "camera_offline": {
      "push": true,
      "email": true,
      "severity_threshold": "P2"
    },
    "motion_detected": {
      "push": true,
      "email": false,
      "cameras": ["cam_001", "cam_002"],
      "quiet_hours": {
        "start": "22:00",
        "end": "07:00"
      }
    }
  }
}
```

---

## Rate Limiting e Agregação

### Regras de Throttling

- **Por evento:** Máximo 1 notificação por câmera a cada 60s
- **Por tipo:** Máximo 10 notificações do mesmo tipo por hora
- **Global:** Máximo 100 notificações por usuário por dia

### Agregação Inteligente

Eventos repetitivos são agregados:
- "5 câmeras offline nos últimos 10 minutos"
- "23 detecções de movimento na Câmera 01"

---

## Fallbacks

### Ordem de Fallback

1. Push Notification (FCM/APNs)
2. WebSocket (se app ativo)
3. Email (para P1/P2)
4. SMS (configurável para P1)
5. Webhook (integrações externas)

---

## Consequências

### Positivas
- Resposta rápida a incidentes
- Maior confiabilidade operacional
- Flexibilidade de configuração
- Auditoria completa de notificações

### Negativas / Trade-offs
- Complexidade de integração com FCM/APNs
- Custo de SMS para fallback
- Manutenção de tokens atualizados

---

## Alternativas Rejeitadas

### Polling do App
- ❌ Consumo excessivo de bateria
- ❌ Latência inaceitável

### WebSocket Exclusivo
- ❌ Não funciona com app fechado
- ❌ Dependência de conexão ativa

### Email como Canal Principal
- ❌ Latência muito alta
- ❌ Baixa taxa de abertura

---

## Requisitos Não Funcionais

- Latência máxima de entrega: 5s (P1), 30s (P2), 5min (P3/P4)
- Taxa de entrega: > 99.5%
- Disponibilidade do serviço: 99.9%
- Retry em caso de falha: 3 tentativas com backoff exponencial

---

## Referências

- Firebase Cloud Messaging Documentation
- Apple Push Notification Service Guide
- Android Notification Best Practices
- OWASP Push Notification Security
