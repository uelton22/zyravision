# Stack Tecnologica Oficial — ZyraVision

## Versoes Obrigatorias

| Camada | Tecnologia | Versao | Justificativa |
|--------|-----------|--------|---------------|
| Backend | Laravel | 11.x (LTS) | Estavel, APIs maduras, suporte longo |
| Backend | PHP | 8.2.x | Compativel Laravel 11, performance |
| Frontend | Vue.js | 3.4.x | Composition API, TypeScript nativo |
| Bridge | Inertia.js | 1.x | Ponte Laravel ↔ Vue sem SPA complexa |
| Estilos | Tailwind CSS | 3.4.x | Estavel, sem breaking changes do 4.x |
| Tipagem | TypeScript | 5.x | Tipagem forte obrigatoria |
| Build | Vite | 5.x | Build rapido, HMR, integra Laravel |
| Runtime | Node.js | 20 LTS | Estavel para Vite e tooling |
| Banco | PostgreSQL | 15+ | JSONB, partitioning, performance |
| Cache | Redis | 7+ | Cache, queues, pub/sub, sessions |
| Mobile | Flutter | 3.x | Cross-platform, video performance |
| Mobile | Dart | 3.x | Tipagem forte, null safety |
| Media | FFmpeg | 6+ | Transcodificacao, HLS, DASH |
| RTSP | MediaMTX | latest | RTSP server, relay, proxy |
| Search | MeiliSearch | latest | Busca rapida de gravacoes |
| AI | Python | 3.11+ | PyTorch, OpenCV, YOLO |

## Restricoes

- **NUNCA** Tailwind 4.x (breaking changes)
- **NUNCA** Alpine.js (conflita com Vue)
- **NUNCA** MySQL/MariaDB (usar PostgreSQL)
- **NUNCA** Options API no Vue (usar Composition API)
- **NUNCA** JavaScript puro no frontend (usar TypeScript)
- **NUNCA** React Native para mobile (usar Flutter)

## Bibliotecas Frontend Aprovadas

| Biblioteca | Uso |
|-----------|-----|
| @inertiajs/vue3 | Bridge Laravel ↔ Vue |
| @heroicons/vue | Icones |
| @headlessui/vue | Modais, dropdowns, transitions |
| @vueuse/core | Composables utilitarios |
| chart.js + vue-chartjs | Graficos |
| hls.js | Player HLS |
| video.js | Player de video |
| zod | Validacao de schemas |
| date-fns | Manipulacao de datas |
| pinia | State management (se necessario) |

## Bibliotecas Backend Aprovadas

| Package | Uso |
|---------|-----|
| laravel/sanctum | Autenticacao |
| laravel/horizon | Queue management |
| spatie/laravel-permission | RBAC |
| spatie/laravel-multitenancy | Multi-tenant |
| spatie/laravel-activitylog | Audit logs |
| spatie/laravel-medialibrary | Media management |
| laravel/scout + meilisearch | Search |
| predis/predis | Redis client |
| league/flysystem-* | Storage abstraction |

## Bibliotecas Mobile (Flutter)

| Package | Uso |
|---------|-----|
| flutter_riverpod | State management |
| dio | HTTP client |
| go_router | Navegacao |
| hive / sqflite | Cache local |
| firebase_messaging | Push notifications |
| flutter_webrtc | WebRTC player |
| video_player | Video playback |
| flutter_secure_storage | Token storage |
| connectivity_plus | Network state |

## Infraestrutura

| Componente | Tecnologia |
|-----------|-----------|
| Containers | Docker + Docker Compose |
| Orchestration | Kubernetes (producao) |
| Reverse Proxy | Nginx / Traefik |
| CI/CD | GitHub Actions |
| Monitoring | Prometheus + Grafana |
| Logging | Loki / ELK Stack |
| Object Storage | MinIO (on-prem) / S3 (cloud) |
| WebSocket | Soketi / Laravel Reverb |
