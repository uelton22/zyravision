# Mobile API Specification — ZyraVision

## Visao Geral

Esta especificacao detalha os aspectos da API otimizados para o app mobile Flutter.

## Autenticacao Mobile

### Login

```http
POST /api/v1/auth/mobile/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password",
  "deviceId": "550e8400-e29b-41d4-a716-446655440000",
  "deviceName": "iPhone 15 Pro",
  "platform": "ios",
  "appVersion": "1.0.0",
  "osVersion": "17.2"
}
```

Response:
```json
{
  "data": {
    "token": "1|abc123def456...",
    "refreshToken": "rt_xyz789...",
    "expiresAt": "2026-02-07T14:00:00Z",
    "user": {
      "id": "uuid",
      "name": "John Doe",
      "email": "user@example.com",
      "role": "admin",
      "tenantId": "uuid",
      "tenantName": "Empresa XYZ",
      "permissions": ["cameras.view", "cameras.create", "..."],
      "avatarUrl": null
    }
  }
}
```

### Device Registration

```http
POST /api/v1/devices/register
Authorization: Bearer {token}

{
  "deviceId": "uuid",
  "platform": "ios",
  "pushToken": "fcm_or_apns_token_here",
  "appVersion": "1.0.0",
  "model": "iPhone 15 Pro",
  "osVersion": "17.2"
}
```

## Dashboard Mobile (Single Request)

Endpoint otimizado que retorna todos os dados do dashboard em uma unica request:

```http
GET /api/v1/mobile/dashboard
Authorization: Bearer {token}
```

Response:
```json
{
  "data": {
    "stats": {
      "totalCameras": 24,
      "onlineCameras": 22,
      "offlineCameras": 2,
      "storageUsedGb": 450,
      "storageTotalGb": 1000,
      "storagePercent": 45,
      "alertsToday": 7,
      "unreadAlerts": 3
    },
    "recentAlerts": [
      {
        "id": "uuid",
        "title": "Camera Offline",
        "severity": "critical",
        "cameraName": "Estacionamento",
        "createdAt": "2026-02-06T10:30:00Z"
      }
    ],
    "priorityCameras": [
      {
        "id": "uuid",
        "name": "Entrada Principal",
        "status": "online",
        "thumbnailUrl": "/api/v1/cameras/uuid/thumbnail?w=320",
        "hasRecording": true
      }
    ]
  },
  "meta": {
    "generatedAt": "2026-02-06T14:00:00Z",
    "cacheControl": "max-age=30"
  }
}
```

## Cameras Summary (Mobile Optimized)

```http
GET /api/v1/mobile/cameras/summary?fields=id,name,status,thumbnailUrl
Authorization: Bearer {token}
If-Modified-Since: Wed, 05 Feb 2026 14:00:00 GMT
```

Response (304 Not Modified se sem mudancas, ou):
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Entrada Principal",
      "status": "online",
      "thumbnailUrl": "/api/v1/cameras/uuid/thumbnail?w=320&h=180"
    }
  ],
  "meta": {
    "total": 24,
    "lastModified": "2026-02-06T14:00:00Z"
  }
}
```

## Thumbnails Otimizados

```http
GET /api/v1/cameras/{id}/thumbnail?w=320&h=180&quality=75
Authorization: Bearer {token}
```

- `w`: largura (default 640)
- `h`: altura (default 360)
- `quality`: qualidade JPEG 1-100 (default 80)
- Cache: 10s com ETag

## Streaming Mobile

### HLS (Padrao)
```http
GET /api/v1/streams/{cameraId}/hls
Authorization: Bearer {token}

Response:
{
  "data": {
    "url": "https://stream.zyravision.com/tenant/camera/index.m3u8?token=temp_token",
    "expiresAt": "2026-02-06T15:00:00Z",
    "qualities": ["360p", "480p", "720p", "1080p"]
  }
}
```

### WebRTC (Baixa Latencia)
```http
POST /api/v1/streams/{cameraId}/webrtc/offer
Authorization: Bearer {token}

Body: { "sdp": "webrtc_offer_sdp" }

Response: { "data": { "sdp": "webrtc_answer_sdp", "iceServers": [...] } }
```

## Push Notifications

### Formato de Payload

```json
{
  "notification": {
    "title": "Camera Offline",
    "body": "Camera 'Estacionamento' perdeu conexao",
    "image": "https://thumbnails.zyravision.com/uuid.jpg"
  },
  "data": {
    "type": "camera.offline",
    "alertId": "uuid",
    "cameraId": "uuid",
    "severity": "critical",
    "timestamp": "2026-02-06T10:30:00Z",
    "deepLink": "zyravision://cameras/uuid"
  }
}
```

### Tipos de Notificacao

| Tipo | Severidade | Descricao |
|------|-----------|-----------|
| camera.offline | P1 | Camera desconectou |
| camera.online | P4 | Camera reconectou |
| storage.critical | P1 | Storage > 90% |
| storage.warning | P3 | Storage > 80% |
| ai.person_detected | P2 | Pessoa detectada |
| ai.motion_detected | P3 | Movimento detectado |
| recording.failed | P2 | Falha na gravacao |
| system.update | P4 | Atualizacao disponivel |

## Sync API (Offline Support)

### Pull (Download changes)

```http
POST /api/v1/mobile/sync/pull
Authorization: Bearer {token}

{
  "since": "2026-02-06T10:00:00Z",
  "entities": ["cameras", "alerts", "notifications"],
  "limit": 100
}
```

Response:
```json
{
  "data": {
    "cameras": {
      "created": [],
      "updated": [{ "id": "uuid", "status": "offline", "updatedAt": "..." }],
      "deleted": ["uuid"]
    },
    "alerts": {
      "created": [{ "id": "uuid", "..." }],
      "updated": [],
      "deleted": []
    }
  },
  "meta": {
    "syncToken": "sync_abc123",
    "syncedAt": "2026-02-06T14:00:00Z",
    "hasMore": false
  }
}
```

### Push (Upload offline actions)

```http
POST /api/v1/mobile/sync/push
Authorization: Bearer {token}

{
  "actions": [
    {
      "id": "local_uuid",
      "type": "alert.acknowledge",
      "payload": { "alertId": "uuid" },
      "timestamp": "2026-02-06T10:30:00Z"
    }
  ]
}
```

Response:
```json
{
  "data": {
    "applied": 1,
    "conflicts": 0,
    "rejected": 0,
    "results": [
      { "localId": "local_uuid", "status": "applied", "serverId": "uuid" }
    ]
  }
}
```

## Economia de Dados

### Sparse Fieldsets
```
GET /api/v1/cameras?fields=id,name,status
→ Retorna apenas campos solicitados
```

### Compressao
```
Accept-Encoding: gzip, br
→ Response comprimido (~70% menor)
```

### Paginacao Otimizada
```
GET /api/v1/cameras?per_page=10  (mobile default menor)
```

### Conditional Requests
```
If-Modified-Since: Wed, 05 Feb 2026 14:00:00 GMT
If-None-Match: "etag_abc123"
→ 304 Not Modified quando sem mudancas
```
