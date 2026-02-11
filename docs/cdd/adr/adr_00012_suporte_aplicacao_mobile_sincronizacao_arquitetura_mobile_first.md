# ADR-00012 — Suporte a Aplicação Mobile, Sincronização e Arquitetura Mobile-First do ZyraVision

**Status:** Aprovado  
**Data:** 2026-01-21  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

## Contexto
O ZyraVision não deve ser apenas uma aplicação web. Em cenários reais de grandes projetos de CFTV (corporativos, industriais, cidades inteligentes, segurança privada), o **uso mobile é crítico** para:
- Supervisores em campo
- Equipes de resposta rápida
- Operadores fora do NOC
- Acesso emergencial

Portanto, o sistema deve ser **nativamente preparado para mobile**, mesmo que o app mobile não seja entregue no MVP inicial.

Grandes plataformas (Genetec, Milestone, Avigilon, Verkada) projetam o backend **desde o início** para suportar aplicações móveis com sincronização, notificações, segurança reforçada e consumo eficiente de dados.

## Decisão
O ZyraVision adotará uma **arquitetura Mobile-Ready**, com backend, APIs, autenticação, eventos e streaming **explicitamente projetados** para suportar aplicativos mobile (iOS/Android).

A IA deve assumir que:
- O mobile **é um primeiro cidadão** da plataforma
- Nenhuma funcionalidade crítica pode depender apenas de web
- Todas as APIs devem ser consumíveis por mobile

## Estratégia de Mobile

### Abordagem Recomendada
- **Web:** Vue 3 + Inertia
- **Mobile App (futuro):**
  - Flutter **OU**
  - React Native
- **API:** REST + WebSocket + Webhooks

O backend Laravel será **100% desacoplado da UI**.

## Requisitos Técnicos Obrigatórios

### 1. API Mobile-Friendly
- RESTful APIs versionadas (`/api/v1`)
- Payloads compactos (JSON otimizado)
- Paginação obrigatória
- Filtros server-side
- Responses consistentes

### 2. Autenticação Mobile
- Laravel Sanctum (tokens pessoais)
- Tokens por dispositivo
- Refresh tokens
- Revogação remota de sessões
- Escopo por perfil

Exemplo:
```json
{
  "token": "jwt_or_sanctum_token",
  "device_id": "ios-uuid",
  "expires_at": "2026-02-01T00:00:00Z"
}
```

### 3. Gerenciamento de Dispositivos
Criar entidade:
- `user_devices`

Campos:
- user_id
- device_id
- device_type (ios/android/web)
- push_token
- last_seen_at
- revoked_at

## Streaming para Mobile

### Protocolos Recomendados
- **HLS** (principal)
- **LL-HLS** (baixa latência, quando possível)
- **WebRTC** (futuro / eventos críticos)

### Adaptações Mobile
- Bitrate adaptativo obrigatório
- Resolução reduzida por padrão
- Troca dinâmica de qualidade
- Limite de streams simultâneos

## Sincronização de Dados

### Offline-First (Onde Aplicável)
- Cache local (preferências, listas)
- Revalidação automática
- Sincronização incremental

### Eventos em Tempo Real
- WebSockets para:
  - Status de câmeras
  - Alertas críticos
- Fallback para polling

## Push Notifications (Obrigatório)

### Tipos de Notificação
- Câmera offline
- Detecção de movimento
- Evento de IA (face, placa)
- Alerta crítico

### Implementação
- Firebase Cloud Messaging (FCM)
- APNs (iOS)

Backend deve:
- Armazenar tokens
- Enviar notificações segmentadas
- Permitir opt-in/out

## Segurança Mobile (Critical)

### Medidas Obrigatórias
- TLS 1.3
- Certificate pinning
- Rate limiting por dispositivo
- Detecção de jailbreak/root (futuro)
- Bloqueio por geolocalização (opcional)

## Performance e Consumo de Dados

- Compressão de payloads
- Limite de requests
- Cache agressivo
- Streaming sob demanda
- Throttling automático

## Permissões e Perfis no Mobile

- Respeitar RBAC existente
- Permissões granulares:
  - Ver live
  - Ver gravações
  - Exportar
  - Controlar PTZ

Mobile **não pode** ter permissões implícitas.

## Observabilidade Mobile

- Logs de uso por dispositivo
- Métricas de crash (futuro)
- Monitoramento de latência
- Alertas de falha de sync

## Anti-Padrões (Proibido)
- APIs exclusivas para web
- Lógica de negócio no frontend
- Payloads grandes sem paginação
- Dependência de cookies
- Streaming sem ABR

## Consequências

### Positivas
- Plataforma preparada para expansão
- Mobile nativo sem refatoração
- Melhor experiência do usuário
- Alinhamento com mercado enterprise

### Negativas
- Mais complexidade inicial
- Maior rigor em APIs

Essas consequências são **esperadas e desejáveis**.

## Referências de Mercado
- Genetec Mobile
- Milestone Mobile
- Verkada Mobile
- Azure Mobile Architecture
- Firebase Best Practices

## Instrução Final para IA
> O ZyraVision deve ser desenvolvido como uma **plataforma Mobile-Ready**.  
> Toda decisão técnica deve considerar o impacto em aplicações mobile atuais ou futuras.

---

