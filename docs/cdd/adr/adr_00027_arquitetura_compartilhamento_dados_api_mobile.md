# ADR-0027: Arquitetura de Compartilhamento de Dados e API Mobile

## Status
**Aprovado** — 2026-02-06

## Contexto

O ZyraVision precisa de uma arquitetura que permita compartilhamento eficiente de dados entre o backend Laravel, o frontend Vue (web) e o app Flutter (mobile). A API deve ser projetada para servir ambas as plataformas de forma otimizada, considerando limitacoes de rede mobile, cache, sincronizacao e tempo real.

### ADRs Relacionadas
- ADR-0001: Arquitetura Geral
- ADR-0016: Versionamento de APIs
- ADR-0026: Compatibilidade Mobile Completa

## Decisao

### 1. Arquitetura de API em Camadas

```
                    ┌─────────────────┐
                    │   API Gateway    │
                    │  (Rate Limit,    │
                    │   Auth, CORS)    │
                    └────────┬────────┘
                             │
                    ┌────────┴────────┐
                    │  Laravel API     │
                    │  /api/v1/...     │
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
     ┌────────┴───┐  ┌──────┴─────┐ ┌─────┴──────┐
     │  Web Vue   │  │ Mobile API │ │  WebSocket  │
     │ (Inertia)  │  │  (REST)    │ │  (Realtime) │
     └────────────┘  └────────────┘ └────────────┘
```

### 2. Camadas de Comunicacao

#### REST API (Dados estruturados)
- CRUD de recursos
- Autenticacao e autorizacao
- Bulk operations
- File uploads

#### WebSocket (Tempo real)
- Status de cameras (online/offline)
- Alertas e notificacoes
- Atualizacao de thumbnails
- Metricas de streaming

#### Server-Sent Events (Unidirecional)
- Logs em tempo real
- Progresso de exportacao
- Status de jobs

### 3. Formato de Response Padronizado

#### Response de Recurso Unico
```json
{
  "data": {
    "id": "uuid",
    "type": "camera",
    "attributes": {
      "name": "Entrada Principal",
      "rtspUrl": "rtsp://192.168.1.100/stream1",
      "status": "online",
      "createdAt": "2026-01-21T10:30:00Z",
      "updatedAt": "2026-01-21T14:00:00Z"
    },
    "relationships": {
      "tenant": { "data": { "id": "uuid", "type": "tenant" } },
      "streams": { "data": [{ "id": "uuid", "type": "stream" }] }
    },
    "meta": {
      "lastHeartbeat": "2026-01-21T14:30:00Z",
      "thumbnailUrl": "/api/v1/cameras/uuid/thumbnail"
    }
  }
}
```

#### Response de Colecao (Paginada)
```json
{
  "data": [ ... ],
  "meta": {
    "currentPage": 1,
    "perPage": 25,
    "total": 100,
    "lastPage": 4
  },
  "links": {
    "first": "/api/v1/cameras?page=1",
    "last": "/api/v1/cameras?page=4",
    "prev": null,
    "next": "/api/v1/cameras?page=2"
  }
}
```

### 4. Estrategia de Cache

#### Cache HTTP (Headers)

```
Cache-Control: private, max-age=60
ETag: "abc123"
Last-Modified: Wed, 21 Jan 2026 14:30:00 GMT
Vary: Accept, Authorization, X-Platform
```

#### Cache por Tipo de Dado

| Recurso | Cache TTL | Estrategia |
|---------|-----------|------------|
| Lista de cameras | 60s | ETag + stale-while-revalidate |
| Detalhes camera | 30s | ETag |
| Thumbnails | 10s | Cache-Control + refresh |
| Status (real-time) | 0s | WebSocket only |
| Configuracoes | 5min | ETag |
| Usuarios/Perfis | 5min | ETag |
| Recordings list | 30s | ETag |
| Dashboard stats | 30s | Cache-Control |

#### Cache Mobile (Offline)

```dart
// Estrategia: Network First, Cache Fallback
class CacheStrategy {
  // Dados criticos: sempre buscar na rede
  // Dados de referencia: cache com TTL
  // Thumbnails: cache LRU (max 100MB)
  // Configuracoes: cache permanente com sync
}
```

### 5. Sincronizacao Mobile

#### Sync Protocol

```
1. App inicia → GET /api/v1/mobile/sync/status
   Response: { lastSync: "2026-01-21T14:00:00Z", pendingChanges: 3 }

2. Pull changes → POST /api/v1/mobile/sync/pull
   Body: { since: "2026-01-21T14:00:00Z", entities: ["cameras", "alerts"] }
   Response: { changes: [...], syncToken: "token123" }

3. Push offline actions → POST /api/v1/mobile/sync/push
   Body: { actions: [{ type: "camera.ptz", payload: {...}, timestamp: "..." }] }
   Response: { applied: 2, conflicts: 0, rejected: 0 }
```

#### Resolucao de Conflitos
- **Server wins**: Para dados de configuracao
- **Last write wins**: Para dados de usuario (preferencias)
- **Manual merge**: Para dados criticos (acoes pendentes)

### 6. Compartilhamento Web ↔ Mobile

#### Shared Types (TypeScript → Dart)

Manter types sincronizados entre plataformas:

```typescript
// types/api.ts (Web)
interface Camera {
  id: string
  name: string
  rtspUrl: string
  status: 'online' | 'offline' | 'error'
  tenantId: string
}
```

```dart
// models/camera.dart (Mobile)
class Camera {
  final String id;
  final String name;
  final String rtspUrl;
  final CameraStatus status;
  final String tenantId;
}
```

#### Shared API Contract (OpenAPI)

Gerar tipos para ambas as plataformas a partir do OpenAPI spec:
- Web: openapi-typescript
- Mobile: openapi-generator (Dart)

### 7. WebSocket Channels

```
// Canais por tenant
tenant.{tenantId}:
  - camera.status.changed
  - alert.created
  - storage.threshold.reached

// Canais por camera
camera.{cameraId}:
  - stream.started
  - stream.stopped
  - recording.started
  - ai.event.detected

// Canal pessoal
user.{userId}:
  - notification.received
  - device.revoked
```

### 8. Rate Limiting por Plataforma

| Plataforma | Limite | Burst |
|-----------|--------|-------|
| Web (autenticado) | 120 req/min | 200 |
| Mobile (autenticado) | 60 req/min | 100 |
| Mobile (background) | 10 req/min | 20 |
| Webhook | 30 req/min | 50 |
| API key (externo) | Configuravel | Configuravel |

## Alternativas Consideradas

1. **GraphQL** — Descartado: complexidade adicional, REST com sparse fieldsets atende
2. **gRPC** — Descartado: sem suporte nativo em browsers, REST mais universal
3. **APIs separadas web/mobile** — Descartado: duplicacao de logica

## Consequencias

### Positivas
- API unica simplifica manutencao
- Cache HTTP padrao funciona em ambas plataformas
- WebSocket garante real-time sem polling
- OpenAPI como contrato compartilhado

### Negativas
- Sparse fieldsets adicionam complexidade no backend
- Sincronizacao offline requer logica adicional
- WebSocket requer infraestrutura adicional (Redis, pusher/soketi)

## Metricas de Validacao

- API response time p95 < 200ms
- WebSocket delivery < 100ms
- Sync time < 10s para 1000 registros
- Cache hit rate > 80% em mobile
- Zero data leaks cross-tenant
