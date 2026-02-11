# Arquitetura do Sistema — ZyraVision

## Visao Macro

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CLIENTS                                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐   │
│  │ Web App  │  │ Mobile   │  │ API      │  │ Integracao       │   │
│  │ Vue 3    │  │ Flutter  │  │ Externa  │  │ Webhook/MQTT     │   │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────────┬─────────┘   │
└───────┼──────────────┼──────────────┼────────────────┼─────────────┘
        │              │              │                │
        └──────────────┼──────────────┘                │
                       │                               │
┌──────────────────────┼───────────────────────────────┼─────────────┐
│                 API GATEWAY / LOAD BALANCER                         │
│              (Nginx / Traefik / AWS ALB)                            │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  Rate Limiting │ CORS │ TLS Termination │ Auth │ Routing    │   │
│  └─────────────────────────────────────────────────────────────┘   │
└──────────────────────┬─────────────────────────────────────────────┘
                       │
┌──────────────────────┼─────────────────────────────────────────────┐
│                 CONTROL PLANE (Laravel)                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐   │
│  │ Auth &   │  │ Camera   │  │ Stream   │  │ Storage          │   │
│  │ RBAC     │  │ Manager  │  │ Orchest. │  │ Manager          │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐   │
│  │ Tenant   │  │ Notif.   │  │ Audit    │  │ AI Orchestrator  │   │
│  │ Manager  │  │ Service  │  │ Logger   │  │                  │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘   │
└──────────┬───────────┬───────────┬───────────┬─────────────────────┘
           │           │           │           │
    ┌──────┴──┐ ┌──────┴──┐ ┌─────┴───┐ ┌────┴────┐
    │ Postgres│ │ Redis   │ │ Queue   │ │WebSocket│
    │ 15+    │ │ 7+      │ │ Horizon │ │ Soketi  │
    └─────────┘ └─────────┘ └────┬────┘ └─────────┘
                                  │
┌─────────────────────────────────┼──────────────────────────────────┐
│                 MEDIA PLANE                                        │
│  ┌──────────┐  ┌──────────┐  ┌─┴────────┐  ┌──────────────────┐   │
│  │ MediaMTX │  │ FFmpeg   │  │ FFmpeg   │  │ FFmpeg           │   │
│  │ (RTSP)   │  │ Worker 1 │  │ Worker 2 │  │ Worker N         │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘   │
└──────────────────────┬─────────────────────────────────────────────┘
                       │
┌──────────────────────┼─────────────────────────────────────────────┐
│                 AI PLANE (Microservicos Python)                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐   │
│  │ Motion   │  │ Person   │  │ Face     │  │ LPR              │   │
│  │ Detect   │  │ Detect   │  │ Recog    │  │ (Placas)         │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘   │
│  [GPU: NVIDIA CUDA / Intel OpenVINO / CPU Fallback]                │
└──────────────────────┬─────────────────────────────────────────────┘
                       │
┌──────────────────────┼─────────────────────────────────────────────┐
│                 STORAGE PLANE                                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐   │
│  │ Local    │  │ NFS/CIFS │  │ MinIO    │  │ AWS S3           │   │
│  │ SSD/NVMe │  │ NAS      │  │ (S3)     │  │ Azure Blob       │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘   │
│  Organizacao: /storage/{tenant_id}/{camera_id}/{date}/             │
└────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────┐
│                 OBSERVABILIDADE                                    │
│  Prometheus │ Grafana │ Loki │ AlertManager │ ELK Stack            │
└────────────────────────────────────────────────────────────────────┘
```

## Fluxos Principais

### 1. Visualizacao ao Vivo
```
Camera RTSP → MediaMTX → FFmpeg (HLS) → Nginx → Player Web/Mobile
              ↓
         Laravel (status via Redis)
```

### 2. Gravacao
```
Camera RTSP → FFmpeg → Segmentos HLS → Storage (tenant_id/camera_id/)
              ↓
         Laravel (metadados → PostgreSQL)
```

### 3. Deteccao IA
```
Camera RTSP → FFmpeg (frames) → AI Service (GPU) → Evento → Redis → Laravel
                                                                    ↓
                                                              Push Notification
```

### 4. Playback
```
Client → Laravel API (busca segmentos) → Storage → Nginx → Player
```

## Comunicacao entre Servicos

| De | Para | Protocolo | Uso |
|----|------|-----------|-----|
| Client → API | REST/WebSocket | CRUD, real-time |
| API → Media | Redis Queue | Start/stop streams |
| Media → API | Redis Pub/Sub | Status updates |
| API → AI | Redis Queue | Processar frames |
| AI → API | Redis Pub/Sub | Eventos detectados |
| API → DB | TCP | Queries PostgreSQL |
| API → Cache | TCP | Redis cache/session |
| API → Push | HTTP | FCM/APNs |

## Escalabilidade

| Camada | Estrategia |
|--------|-----------|
| API | Horizontal (multiplas instancias Laravel) |
| Media | Horizontal (pool de FFmpeg workers) |
| AI | Horizontal (multiplos containers GPU) |
| DB | Vertical + Read replicas |
| Cache | Redis Cluster |
| Storage | Object Storage (S3/MinIO) |
