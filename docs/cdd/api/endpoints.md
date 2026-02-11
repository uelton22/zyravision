# Endpoints Completos — ZyraVision API v1

## Autenticacao

| Metodo | Endpoint | Descricao | Auth |
|--------|----------|-----------|------|
| POST | /api/v1/auth/login | Login (email/senha) | Nao |
| POST | /api/v1/auth/register | Registro (se habilitado) | Nao |
| POST | /api/v1/auth/refresh | Refresh token | Token |
| POST | /api/v1/auth/logout | Logout | Token |
| POST | /api/v1/auth/logout-all | Revogar todos tokens | Token |
| POST | /api/v1/auth/forgot-password | Solicitar reset | Nao |
| POST | /api/v1/auth/reset-password | Resetar senha | Nao |
| POST | /api/v1/auth/mobile/login | Login mobile com device | Nao |
| POST | /api/v1/auth/biometric/enroll | Registrar biometria | Token |
| POST | /api/v1/auth/biometric/verify | Login biometrico | Nao |

## Usuarios

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/users | Listar usuarios do tenant | admin |
| POST | /api/v1/users | Criar usuario | admin |
| GET | /api/v1/users/{id} | Detalhes do usuario | admin |
| PUT | /api/v1/users/{id} | Atualizar usuario | admin |
| DELETE | /api/v1/users/{id} | Remover usuario | admin |
| GET | /api/v1/users/me | Perfil do usuario logado | viewer |
| PUT | /api/v1/users/me | Atualizar proprio perfil | viewer |

## Tenants

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/tenants | Listar tenants | super_admin |
| POST | /api/v1/tenants | Criar tenant | super_admin |
| GET | /api/v1/tenants/{id} | Detalhes do tenant | super_admin |
| PUT | /api/v1/tenants/{id} | Atualizar tenant | super_admin |
| DELETE | /api/v1/tenants/{id} | Remover tenant | super_admin |
| GET | /api/v1/tenants/{id}/stats | Estatisticas do tenant | admin |

## Cameras

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/cameras | Listar cameras | viewer |
| POST | /api/v1/cameras | Cadastrar camera | admin |
| GET | /api/v1/cameras/{id} | Detalhes da camera | viewer |
| PUT | /api/v1/cameras/{id} | Atualizar camera | admin |
| DELETE | /api/v1/cameras/{id} | Remover camera | admin |
| GET | /api/v1/cameras/{id}/thumbnail | Thumbnail atual | viewer |
| POST | /api/v1/cameras/{id}/probe | Testar conexao RTSP | admin |
| GET | /api/v1/cameras/discover | Descobrir cameras ONVIF | admin |

## Streams

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/streams | Listar streams ativos | viewer |
| POST | /api/v1/streams/{cameraId}/start | Iniciar stream | operator |
| POST | /api/v1/streams/{cameraId}/stop | Parar stream | operator |
| GET | /api/v1/streams/{cameraId}/status | Status do stream | viewer |
| GET | /api/v1/streams/{cameraId}/hls | URL HLS do stream | viewer |
| GET | /api/v1/streams/{cameraId}/webrtc | Sessao WebRTC | viewer |

## Gravacoes (Recordings)

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/recordings | Listar gravacoes | viewer |
| GET | /api/v1/recordings/{id} | Detalhes da gravacao | viewer |
| DELETE | /api/v1/recordings/{id} | Remover gravacao | admin |
| GET | /api/v1/recordings/{id}/play | URL de playback | viewer |
| POST | /api/v1/recordings/{id}/export | Exportar clip | operator |
| GET | /api/v1/cameras/{id}/recordings | Gravacoes por camera | viewer |
| GET | /api/v1/cameras/{id}/timeline | Timeline de gravacoes | viewer |

## Alertas

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/alerts | Listar alertas | viewer |
| GET | /api/v1/alerts/{id} | Detalhes do alerta | viewer |
| PUT | /api/v1/alerts/{id}/acknowledge | Reconhecer alerta | operator |
| PUT | /api/v1/alerts/{id}/resolve | Resolver alerta | operator |
| GET | /api/v1/alerts/unread | Alertas nao lidos | viewer |
| GET | /api/v1/alerts/stats | Estatisticas de alertas | operator |

## AI Events

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/ai/events | Listar eventos de IA | viewer |
| GET | /api/v1/ai/events/{id} | Detalhes do evento | viewer |
| GET | /api/v1/ai/cameras/{id}/config | Config IA da camera | admin |
| PUT | /api/v1/ai/cameras/{id}/config | Atualizar config IA | admin |
| GET | /api/v1/ai/models | Modelos de IA disponiveis | admin |
| GET | /api/v1/ai/gpu/status | Status das GPUs | admin |

## Storage

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/storage/pools | Listar pools de storage | admin |
| POST | /api/v1/storage/pools | Criar pool de storage | admin |
| GET | /api/v1/storage/usage | Uso de armazenamento | admin |
| GET | /api/v1/storage/health | Saude do storage | admin |

## Devices (Mobile)

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/devices | Listar devices do usuario | viewer |
| POST | /api/v1/devices/register | Registrar device | viewer |
| PUT | /api/v1/devices/{id}/push-token | Atualizar push token | viewer |
| DELETE | /api/v1/devices/{id} | Revogar device | viewer |

## Mobile-Specific

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/mobile/dashboard | Dashboard resumido | viewer |
| GET | /api/v1/mobile/cameras/summary | Cameras resumidas | viewer |
| GET | /api/v1/mobile/notifications | Notificacoes | viewer |
| POST | /api/v1/mobile/sync/pull | Pull sync incremental | viewer |
| POST | /api/v1/mobile/sync/push | Push acoes offline | viewer |

## Notificacoes

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/notifications | Listar notificacoes | viewer |
| PUT | /api/v1/notifications/{id}/read | Marcar como lida | viewer |
| PUT | /api/v1/notifications/read-all | Marcar todas como lidas | viewer |
| GET | /api/v1/notifications/preferences | Preferencias | viewer |
| PUT | /api/v1/notifications/preferences | Atualizar preferencias | viewer |

## Dashboard

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/dashboard/stats | Estatisticas gerais | viewer |
| GET | /api/v1/dashboard/bandwidth | Uso de banda | operator |
| GET | /api/v1/dashboard/storage | Uso de disco | operator |

## Audit Logs

| Metodo | Endpoint | Descricao | Role Min |
|--------|----------|-----------|----------|
| GET | /api/v1/audit-logs | Listar logs de auditoria | admin |
| GET | /api/v1/audit-logs/{id} | Detalhes do log | admin |

## WebSocket Channels (Tempo Real)

Conexao via Laravel Reverb ou Soketi.

### Canais por Tenant
```
tenant.{tenantId}
  Eventos:
  - camera.status.changed    { cameraId, status, timestamp }
  - alert.created            { alertId, severity, cameraId, message }
  - storage.threshold        { poolId, usagePercent, threshold }
  - stream.started           { cameraId, streamUrl }
  - stream.stopped           { cameraId, reason }
```

### Canais por Camera
```
camera.{cameraId}
  Eventos:
  - recording.started        { recordingId, startedAt }
  - recording.stopped        { recordingId, duration }
  - ai.event.detected        { eventType, confidence, metadata }
  - heartbeat                { fps, bitrate, latency }
```

### Canal Pessoal
```
user.{userId}
  Eventos:
  - notification.received    { notificationId, title, body, type }
  - device.revoked           { deviceId }
  - session.expired          {}
```

### Autenticacao WebSocket
```
// Laravel Echo + Sanctum
Echo.private(`tenant.${tenantId}`)
  .listen('CameraStatusChanged', (e) => { ... })
```

---

## Query Parameters Padrao

```
?page=1                    # Pagina
&per_page=25               # Items por pagina
&sort=created_at           # Campo de ordenacao
&order=desc                # Direcao (asc|desc)
&search=termo              # Busca global
&filter[status]=online     # Filtro por campo
&fields=id,name,status     # Sparse fieldsets (mobile)
&include=tenant,streams    # Eager loading
```
