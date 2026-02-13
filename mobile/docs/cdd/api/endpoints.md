# API Endpoints — ZyraVision Mobile

## Base URL

```
Production: https://app.zyravision.com/api
Staging:    https://staging.zyravision.com/api
Local:      http://localhost:8000/api
```

Headers padrao:
```
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
X-Tenant-ID: {tenant_uuid}  (opcional, inferido do token)
```

---

## Autenticacao

### POST /auth/login
Login com email/senha. Retorna tokens de acesso.

**Request:**
```json
{
  "email": "admin@empresa.com",
  "password": "senha123",
  "device_name": "iPhone 15 Pro"
}
```

**Response 200:**
```json
{
  "user": {
    "id": "uuid",
    "name": "Admin",
    "email": "admin@empresa.com",
    "tenant_id": "uuid",
    "roles": ["admin"],
    "permissions": ["cameras.view", "cameras.manage", "recordings.view"]
  },
  "token": "1|abc123...",
  "expires_at": "2024-01-15T12:00:00Z"
}
```

**Erros:**
- 401: Credenciais invalidas
- 422: Validacao (email required, password required)
- 429: Rate limit (5 tentativas/minuto)

### POST /auth/logout
Revoga token atual.

### POST /auth/refresh
Renova token antes de expirar.

### GET /auth/user
Retorna usuario autenticado com roles e permissions.

---

## Dispositivos do Usuario

### POST /user-devices
Registra dispositivo mobile para push notifications.

**Request:**
```json
{
  "device_name": "iPhone 15 Pro",
  "platform": "ios",
  "fcm_token": "fcm_token_here",
  "app_version": "1.0.0",
  "os_version": "17.2"
}
```

### PUT /user-devices/{id}
Atualiza FCM token do dispositivo.

### DELETE /user-devices/{id}
Remove dispositivo (logout remoto).

### GET /user-devices
Lista dispositivos do usuario.

---

## Dashboard

### GET /mobile/dashboard
Dados consolidados do dashboard mobile.

**Response 200:**
```json
{
  "stats": {
    "cameras_total": 50,
    "cameras_online": 42,
    "cameras_offline": 8,
    "cameras_recording": 35,
    "storage_used_gb": 450.5,
    "storage_total_gb": 1000,
    "storage_percentage": 45.05,
    "alerts_today": 12,
    "alerts_unread": 3
  },
  "recent_alerts": [
    {
      "id": "uuid",
      "type": "camera_offline",
      "severity": "warning",
      "camera_name": "Estacionamento",
      "message": "Camera perdeu conexao",
      "created_at": "2024-01-15T14:32:00Z",
      "read": false
    }
  ],
  "cameras_favorites": [
    {
      "id": "uuid",
      "name": "Entrada Principal",
      "status": "online",
      "is_recording": true,
      "snapshot_url": "/api/cameras/{id}/snapshot",
      "stream_path": "cam_abc12345_entrada-principal"
    }
  ]
}
```

---

## Cameras

### GET /cameras
Lista cameras com paginacao e filtros.

**Query Params:**
| Param | Tipo | Default | Descricao |
|-------|------|---------|-----------|
| page | int | 1 | Pagina |
| per_page | int | 15 | Itens por pagina (max 50) |
| search | string | — | Busca por nome (ilike) |
| status | string | — | online, offline, error |
| is_recording | bool | — | Filtra por gravacao ativa |
| group_id | uuid | — | Filtra por grupo |
| sort | string | name | name, status, created_at |
| direction | string | asc | asc, desc |

**Response 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Estacionamento",
      "slug": "estacionamento",
      "manufacturer": "hikvision",
      "model": "DS-2CD2143G2-I",
      "ip_address": "192.168.1.100",
      "status": "online",
      "is_online": true,
      "is_recording": true,
      "codec": "h264",
      "resolution": "1920x1080",
      "fps": 30,
      "snapshot_url": "/api/cameras/uuid/snapshot",
      "recording_quality": "original",
      "stream": {
        "id": "uuid",
        "mediamtx_path": "cam_abc12345_estacionamento",
        "status": "active",
        "hls_url": "/hls/cam_abc12345_estacionamento/index.m3u8"
      },
      "group": {
        "id": "uuid",
        "name": "Area Externa"
      },
      "created_at": "2024-01-10T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 4,
    "per_page": 15,
    "total": 50
  }
}
```

### GET /cameras/{id}
Detalhes de uma camera.

**Response 200:** Camera completa com stream details, recording settings, device info.

### GET /cameras/{id}/snapshot
Retorna snapshot JPEG da camera (via FFmpeg do MediaMTX RTSP relay).

**Response:** `image/jpeg` binary

### POST /cameras/{id}/stream/start
Inicia stream da camera no MediaMTX.

### POST /cameras/{id}/stream/stop
Para stream da camera.

### POST /cameras/{id}/recording/start
Inicia gravacao da camera.

### POST /cameras/{id}/recording/stop
Para gravacao da camera.

### POST /cameras/{id}/ptz
Envia comando PTZ para camera.

**Request:**
```json
{
  "action": "move",
  "pan": 0.5,
  "tilt": -0.3,
  "zoom": 0.0,
  "speed": 0.5
}
```

Actions: `move`, `stop`, `preset`, `home`

---

## Gravacoes

### GET /recordings
Lista gravacoes com filtros.

**Query Params:**
| Param | Tipo | Default | Descricao |
|-------|------|---------|-----------|
| page | int | 1 | Pagina |
| per_page | int | 15 | Itens por pagina |
| camera_id | uuid | — | Filtra por camera |
| date_from | datetime | — | Data inicial |
| date_to | datetime | — | Data final |
| has_events | bool | — | Apenas com eventos |

**Response 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "camera_id": "uuid",
      "camera_name": "Estacionamento",
      "started_at": "2024-01-15T08:00:00Z",
      "ended_at": "2024-01-15T18:00:00Z",
      "duration_seconds": 36000,
      "size_bytes": 5368709120,
      "codec": "h264",
      "resolution": "1920x1080",
      "fps": 30,
      "status": "completed",
      "segments_count": 600,
      "events_count": 5
    }
  ],
  "meta": { ... }
}
```

### GET /recordings/{id}
Detalhes de uma gravacao com lista de segmentos.

### GET /recordings/{id}/segments
Lista segmentos de uma gravacao.

**Response 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "sequence": 1,
      "started_at": "2024-01-15T08:00:00Z",
      "ended_at": "2024-01-15T08:01:00Z",
      "duration_seconds": 60,
      "size_bytes": 8945664,
      "storage_backend": "local",
      "playback_url": "/api/recordings/segments/uuid/stream"
    }
  ]
}
```

### GET /recordings/segments/{id}/stream
Stream HLS de um segmento para playback.

### POST /recordings/{id}/clip
Extrai clip de um trecho da gravacao (FFmpeg).

**Request:**
```json
{
  "start_time": "2024-01-15T14:30:00Z",
  "end_time": "2024-01-15T14:35:00Z",
  "format": "mp4"
}
```

**Response 200:**
```json
{
  "clip_url": "/api/recordings/clips/uuid/download",
  "size_bytes": 52428800,
  "duration_seconds": 300,
  "expires_at": "2024-01-15T15:35:00Z"
}
```

### GET /recordings/timeline/{camera_id}
Timeline de gravacoes para uma camera em um dia.

**Query Params:** `date` (Y-m-d)

**Response 200:**
```json
{
  "date": "2024-01-15",
  "camera_id": "uuid",
  "segments": [
    {
      "start": "2024-01-15T08:00:00Z",
      "end": "2024-01-15T08:01:00Z",
      "has_events": false
    },
    {
      "start": "2024-01-15T08:01:00Z",
      "end": "2024-01-15T08:02:00Z",
      "has_events": true,
      "events": [
        {
          "type": "motion",
          "at": "2024-01-15T08:01:23Z"
        }
      ]
    }
  ]
}
```

---

## Alertas / Eventos

### GET /analytics/events
Lista eventos de analytics.

**Query Params:**
| Param | Tipo | Default | Descricao |
|-------|------|---------|-----------|
| page | int | 1 | Pagina |
| severity | string | — | critical, warning, info |
| type | string | — | camera_offline, motion, storage_full |
| camera_id | uuid | — | Filtra por camera |
| read | bool | — | Filtra por lido/nao lido |
| date_from | datetime | — | Data inicial |

**Response 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "type": "camera_offline",
      "severity": "warning",
      "camera_id": "uuid",
      "camera_name": "Estacionamento",
      "message": "Camera perdeu conexao",
      "details": {
        "last_seen": "2024-01-15T14:30:00Z",
        "ip_address": "192.168.1.100"
      },
      "read": false,
      "created_at": "2024-01-15T14:32:00Z"
    }
  ]
}
```

### PUT /analytics/events/{id}/read
Marca evento como lido.

### PUT /analytics/events/read-all
Marca todos eventos como lidos.

---

## Storage

### GET /storage/stats
Estatisticas de armazenamento do tenant.

**Response 200:**
```json
{
  "backends": [
    {
      "id": "uuid",
      "name": "Local SSD",
      "type": "local",
      "status": "healthy",
      "total_bytes": 1073741824000,
      "used_bytes": 483183820800,
      "free_bytes": 590558003200,
      "usage_percentage": 45.0
    }
  ],
  "quota": {
    "storage_limit_gb": 1000,
    "storage_used_gb": 450.5,
    "percentage": 45.05
  }
}
```

---

## Perfil / Configuracoes

### GET /profile
Dados do perfil do usuario.

### PUT /profile
Atualiza perfil (nome, email, avatar).

### PUT /profile/password
Altera senha.

### GET /settings/notifications
Preferencias de notificacao.

**Response 200:**
```json
{
  "push_enabled": true,
  "push_camera_offline": true,
  "push_camera_online": false,
  "push_storage_warning": true,
  "push_motion_detected": true,
  "push_quiet_hours_start": "22:00",
  "push_quiet_hours_end": "07:00",
  "email_daily_report": true,
  "email_weekly_report": false
}
```

### PUT /settings/notifications
Atualiza preferencias de notificacao.

---

## WebSocket (Laravel Reverb)

### Conexao

```
wss://app.zyravision.com/app/{app_key}
Protocol: Pusher
```

### Canais

| Canal | Tipo | Eventos |
|-------|------|---------|
| `private-tenant.{id}` | Private | Eventos do tenant |
| `private-camera.{id}` | Private | Status da camera |
| `private-user.{id}` | Private | Notificacoes do usuario |

### Eventos

#### `camera.status.changed`
```json
{
  "camera_id": "uuid",
  "status": "online|offline|error",
  "is_online": true,
  "is_recording": false,
  "changed_at": "2024-01-15T14:32:00Z"
}
```

#### `camera.stream.started`
```json
{
  "camera_id": "uuid",
  "stream_path": "cam_abc12345_estacionamento",
  "hls_url": "/hls/cam_abc12345_estacionamento/index.m3u8"
}
```

#### `camera.stream.stopped`
```json
{
  "camera_id": "uuid",
  "stream_path": "cam_abc12345_estacionamento"
}
```

#### `recording.started`
```json
{
  "camera_id": "uuid",
  "recording_id": "uuid",
  "quality": "original"
}
```

#### `recording.stopped`
```json
{
  "camera_id": "uuid",
  "recording_id": "uuid",
  "duration_seconds": 3600,
  "size_bytes": 536870912
}
```

#### `alert.created`
```json
{
  "event_id": "uuid",
  "type": "camera_offline",
  "severity": "warning",
  "camera_id": "uuid",
  "camera_name": "Estacionamento",
  "message": "Camera perdeu conexao",
  "created_at": "2024-01-15T14:32:00Z"
}
```

#### `storage.warning`
```json
{
  "usage_percentage": 90.5,
  "used_gb": 905,
  "total_gb": 1000,
  "message": "Armazenamento acima de 90%"
}
```

#### `notification.push`
```json
{
  "title": "Camera Offline",
  "body": "Camera Estacionamento perdeu conexao",
  "data": {
    "type": "camera_offline",
    "camera_id": "uuid",
    "route": "/cameras/uuid"
  }
}
```

---

## Streaming URLs

### HLS (Live View)
```
GET /hls/{mediamtx_path}/index.m3u8
Authorization: Basic base64(zyravision:secret)
```

### WebRTC (Ultra-low latency)
```
POST /webrtc/{mediamtx_path}/whep
Authorization: Basic base64(zyravision:secret)
Content-Type: application/sdp
Body: SDP offer
```

### Playback (Segmento gravado)
```
GET /api/recordings/segments/{id}/stream
Authorization: Bearer {token}
```

---

## Paginacao (Padrao)

Todas as listas seguem o padrao Laravel:

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "...?page=1",
    "last": "...?page=10",
    "prev": null,
    "next": "...?page=2"
  }
}
```

---

## Codigos de Erro

| Codigo | Significado | Acao no Mobile |
|--------|-----------|----------------|
| 200 | Sucesso | Processar dados |
| 201 | Criado | Processar + toast sucesso |
| 204 | Sem conteudo | Acao concluida |
| 400 | Bad Request | Mostrar erro |
| 401 | Nao autenticado | Redirect login |
| 403 | Sem permissao | Mostrar erro permissao |
| 404 | Nao encontrado | Mostrar empty state |
| 422 | Validacao | Mostrar erros nos campos |
| 429 | Rate limit | Retry com backoff |
| 500 | Erro servidor | Toast erro generico |
| 503 | Manutencao | Tela de manutencao |

### Formato de Erro 422

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["O campo email e obrigatorio."],
    "password": ["A senha deve ter pelo menos 8 caracteres."]
  }
}
```

---

## Rate Limits

| Endpoint | Limite |
|----------|--------|
| POST /auth/login | 5/minuto |
| POST /auth/refresh | 10/minuto |
| GET /* | 60/minuto |
| POST /cameras/*/ptz | 30/minuto |
| GET /cameras/*/snapshot | 10/minuto |

Header de resposta: `X-RateLimit-Remaining`, `X-RateLimit-Limit`, `Retry-After`
