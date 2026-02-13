# ZyraVision Mobile — Plano Completo de Desenvolvimento

## 1. Visao Geral

### 1.1 Objetivo
Desenvolver o aplicativo mobile oficial do ZyraVision, uma plataforma SaaS de CFTV/Video Surveillance, utilizando Flutter 3.x com arquitetura em 5 camadas, design profissional e suporte completo a streaming de video em tempo real.

### 1.2 Stack Tecnologica

| Tecnologia | Versao | Uso |
|-----------|--------|-----|
| Flutter | 3.x (stable) | Framework cross-platform |
| Dart | 3.x | Linguagem |
| Riverpod | 2.x | State Management |
| Dio | 5.x | HTTP Client |
| GoRouter | latest | Navegacao declarativa |
| Hive | 4.x | Cache local (NoSQL) |
| flutter_secure_storage | latest | Tokens e credenciais |
| video_player + better_player | latest | HLS Playback |
| flutter_webrtc | latest | WebRTC ultra-low latency |
| firebase_messaging | latest | Push Notifications (FCM) |
| connectivity_plus | latest | Network monitoring |
| freezed + json_serializable | latest | Data classes imutaveis |
| flutter_local_notifications | latest | Notificacoes locais |
| cached_network_image | latest | Cache de thumbnails |
| permission_handler | latest | Permissoes do sistema |
| local_auth | latest | Biometria (Face ID / Fingerprint) |

### 1.3 Plataformas Alvo
- Android 8.0+ (API 26+)
- iOS 15.0+

---

## 2. Arquitetura — 5 Camadas

### 2.1 Diagrama

```
┌─────────────────────────────────────────┐
│              UI LAYER                    │
│  Screens, Widgets, Navigation           │
│  (Flutter Widgets, GoRouter)            │
├─────────────────────────────────────────┤
│          PRESENTATION LAYER             │
│  ViewModels, Controllers, Formatters    │
│  (Riverpod Notifiers, StateNotifiers)   │
├─────────────────────────────────────────┤
│           STATE / STORE LAYER           │
│  Global State, Providers, Cache State   │
│  (Riverpod Providers, AsyncNotifiers)   │
├─────────────────────────────────────────┤
│          DOMAIN / ENGINE LAYER          │
│  Use Cases, Entities, Repositories      │
│  (Business Rules, Validations)          │
├─────────────────────────────────────────┤
│       DATA / INTEGRATION LAYER          │
│  API Client, WebSocket, Local DB        │
│  (Dio, Hive, flutter_webrtc, HLS)      │
└─────────────────────────────────────────┘
```

### 2.2 Descricao das Camadas

#### Camada 1 — UI Layer (`lib/ui/`)
- Widgets visuais puros (Zy* Design System)
- Screens compostas por widgets
- Nenhuma logica de negocio
- Responsiva e adaptativa (phone + tablet)
- Animacoes e transicoes
- Acessibilidade (WCAG 2.1 AA)

#### Camada 2 — Presentation Layer (`lib/presentation/`)
- ViewModels (Riverpod Notifiers)
- Controllers de tela
- Formatters (data, tempo, tamanho de arquivo)
- Mapeamento de estado para UI
- Tratamento de erros para exibicao

#### Camada 3 — State/Store Layer (`lib/state/`)
- Providers globais (Riverpod)
- Estado de autenticacao
- Estado de conectividade
- Cache de dados em memoria
- Sincronizacao offline

#### Camada 4 — Domain/Engine Layer (`lib/domain/`)
- Entidades de dominio (imutaveis com freezed)
- Use Cases (operacoes de negocio)
- Repository Interfaces (contratos)
- Regras de validacao
- Enums e constantes de dominio

#### Camada 5 — Data/Integration Layer (`lib/data/`)
- Implementacao dos Repositories
- API Client (Dio + interceptors)
- WebSocket Client (Laravel Reverb)
- Local Storage (Hive)
- Secure Storage (tokens)
- Video streaming (HLS, WebRTC)
- Push Notifications (FCM/APNs)

### 2.3 Regra de Dependencia
```
UI → Presentation → State → Domain ← Data
```
- Camadas superiores dependem das inferiores
- Domain NAO depende de Data (inversao)
- Data implementa interfaces definidas em Domain

---

## 3. Estrutura de Pastas

```
lib/
├── main.dart                          # Entry point
├── app.dart                           # MaterialApp + Riverpod + GoRouter
│
├── core/                              # Infraestrutura compartilhada
│   ├── config/
│   │   ├── app_config.dart            # URLs, timeouts, feature flags
│   │   ├── env.dart                   # Environment variables
│   │   └── constants.dart             # Constantes globais
│   ├── di/
│   │   └── providers.dart             # Riverpod providers globais
│   ├── network/
│   │   ├── api_client.dart            # Dio setup + interceptors
│   │   ├── api_interceptor.dart       # Auth, retry, logging
│   │   ├── api_response.dart          # Response wrapper
│   │   ├── api_exception.dart         # Exception types
│   │   └── connectivity_service.dart  # Network state monitoring
│   ├── storage/
│   │   ├── secure_storage.dart        # flutter_secure_storage wrapper
│   │   ├── cache_manager.dart         # Hive cache manager
│   │   └── preferences.dart           # SharedPreferences wrapper
│   ├── auth/
│   │   ├── auth_service.dart          # Login, logout, refresh token
│   │   ├── auth_state.dart            # Auth state model
│   │   ├── auth_provider.dart         # Riverpod auth provider
│   │   └── biometric_service.dart     # Face ID / Fingerprint
│   ├── push/
│   │   ├── push_service.dart          # FCM setup + handling
│   │   ├── notification_handler.dart  # Deep link routing
│   │   └── push_provider.dart         # Riverpod push provider
│   ├── streaming/
│   │   ├── hls_player_service.dart    # HLS playback management
│   │   ├── webrtc_service.dart        # WebRTC signaling + connection
│   │   ├── adaptive_quality.dart      # ABR quality switching
│   │   └── pip_service.dart           # Picture-in-Picture
│   ├── sync/
│   │   ├── sync_service.dart          # Offline sync manager
│   │   ├── sync_queue.dart            # Pending actions queue
│   │   └── conflict_resolver.dart     # Conflict resolution
│   ├── theme/
│   │   ├── zy_theme.dart              # ThemeData (light + dark)
│   │   ├── zy_colors.dart             # Color palette tokens
│   │   ├── zy_typography.dart         # Text styles
│   │   ├── zy_spacing.dart            # Spacing scale
│   │   └── zy_shadows.dart            # Shadow tokens
│   └── utils/
│       ├── validators.dart            # Input validators
│       ├── date_formatter.dart        # Date/time formatting
│       ├── file_size_formatter.dart   # Bytes to human readable
│       ├── duration_formatter.dart    # Seconds to HH:MM:SS
│       └── debouncer.dart             # Search debouncing
│
├── domain/                            # Camada 4 — Domain/Engine
│   ├── entities/
│   │   ├── user.dart                  # User entity (freezed)
│   │   ├── tenant.dart                # Tenant entity
│   │   ├── camera.dart                # Camera entity
│   │   ├── device.dart                # Device entity
│   │   ├── stream_info.dart           # Stream entity
│   │   ├── recording.dart             # Recording entity
│   │   ├── recording_segment.dart     # Segment entity
│   │   ├── storage_backend.dart       # StorageBackend entity
│   │   ├── plan.dart                  # Plan entity
│   │   ├── subscription.dart          # Subscription entity
│   │   ├── quota_usage.dart           # QuotaUsage entity
│   │   ├── analytics_event.dart       # AnalyticsEvent entity
│   │   └── user_device.dart           # UserDevice entity
│   ├── repositories/
│   │   ├── auth_repository.dart       # Interface
│   │   ├── camera_repository.dart     # Interface
│   │   ├── device_repository.dart     # Interface
│   │   ├── stream_repository.dart     # Interface
│   │   ├── recording_repository.dart  # Interface
│   │   ├── storage_repository.dart    # Interface
│   │   ├── user_repository.dart       # Interface
│   │   └── analytics_repository.dart  # Interface
│   ├── usecases/
│   │   ├── auth/
│   │   │   ├── login_usecase.dart
│   │   │   ├── logout_usecase.dart
│   │   │   └── refresh_token_usecase.dart
│   │   ├── cameras/
│   │   │   ├── get_cameras_usecase.dart
│   │   │   ├── get_camera_detail_usecase.dart
│   │   │   └── toggle_recording_usecase.dart
│   │   ├── streaming/
│   │   │   ├── start_stream_usecase.dart
│   │   │   ├── stop_stream_usecase.dart
│   │   │   └── get_stream_url_usecase.dart
│   │   ├── recordings/
│   │   │   ├── get_recordings_usecase.dart
│   │   │   ├── play_recording_usecase.dart
│   │   │   └── create_clip_usecase.dart
│   │   └── dashboard/
│   │       └── get_dashboard_usecase.dart
│   └── enums/
│       ├── camera_status.dart
│       ├── stream_status.dart
│       ├── recording_status.dart
│       ├── health_status.dart
│       ├── user_role.dart
│       └── notification_severity.dart
│
├── data/                              # Camada 5 — Data/Integration
│   ├── repositories/
│   │   ├── auth_repository_impl.dart
│   │   ├── camera_repository_impl.dart
│   │   ├── device_repository_impl.dart
│   │   ├── stream_repository_impl.dart
│   │   ├── recording_repository_impl.dart
│   │   ├── storage_repository_impl.dart
│   │   ├── user_repository_impl.dart
│   │   └── analytics_repository_impl.dart
│   ├── datasources/
│   │   ├── remote/
│   │   │   ├── auth_remote_datasource.dart
│   │   │   ├── camera_remote_datasource.dart
│   │   │   ├── stream_remote_datasource.dart
│   │   │   ├── recording_remote_datasource.dart
│   │   │   ├── mobile_remote_datasource.dart
│   │   │   └── user_remote_datasource.dart
│   │   └── local/
│   │       ├── camera_local_datasource.dart
│   │       ├── recording_local_datasource.dart
│   │       └── settings_local_datasource.dart
│   ├── models/
│   │   ├── user_model.dart            # JSON serializable
│   │   ├── camera_model.dart
│   │   ├── device_model.dart
│   │   ├── stream_model.dart
│   │   ├── recording_model.dart
│   │   ├── dashboard_model.dart
│   │   ├── auth_response_model.dart
│   │   └── pagination_model.dart
│   └── mappers/
│       ├── user_mapper.dart           # Model ↔ Entity
│       ├── camera_mapper.dart
│       ├── recording_mapper.dart
│       └── stream_mapper.dart
│
├── state/                             # Camada 3 — State/Store
│   ├── auth/
│   │   ├── auth_notifier.dart
│   │   └── auth_providers.dart
│   ├── cameras/
│   │   ├── camera_list_notifier.dart
│   │   ├── camera_detail_notifier.dart
│   │   └── camera_providers.dart
│   ├── streaming/
│   │   ├── stream_notifier.dart
│   │   └── stream_providers.dart
│   ├── recordings/
│   │   ├── recording_list_notifier.dart
│   │   ├── playback_notifier.dart
│   │   └── recording_providers.dart
│   ├── dashboard/
│   │   ├── dashboard_notifier.dart
│   │   └── dashboard_providers.dart
│   ├── settings/
│   │   ├── theme_notifier.dart
│   │   └── settings_providers.dart
│   └── connectivity/
│       ├── connectivity_notifier.dart
│       └── connectivity_providers.dart
│
├── presentation/                      # Camada 2 — Presentation
│   ├── viewmodels/
│   │   ├── login_viewmodel.dart
│   │   ├── dashboard_viewmodel.dart
│   │   ├── camera_list_viewmodel.dart
│   │   ├── live_view_viewmodel.dart
│   │   ├── playback_viewmodel.dart
│   │   └── settings_viewmodel.dart
│   ├── formatters/
│   │   ├── camera_status_formatter.dart
│   │   ├── storage_formatter.dart
│   │   └── recording_formatter.dart
│   └── controllers/
│       ├── navigation_controller.dart
│       └── app_lifecycle_controller.dart
│
├── ui/                                # Camada 1 — UI
│   ├── screens/
│   │   ├── splash/
│   │   │   └── splash_screen.dart
│   │   ├── auth/
│   │   │   ├── login_screen.dart
│   │   │   ├── forgot_password_screen.dart
│   │   │   └── biometric_screen.dart
│   │   ├── dashboard/
│   │   │   └── dashboard_screen.dart
│   │   ├── cameras/
│   │   │   ├── camera_list_screen.dart
│   │   │   ├── camera_detail_screen.dart
│   │   │   └── camera_grid_screen.dart
│   │   ├── live_view/
│   │   │   ├── live_view_screen.dart
│   │   │   ├── live_view_fullscreen.dart
│   │   │   └── multi_view_screen.dart
│   │   ├── recordings/
│   │   │   ├── recording_list_screen.dart
│   │   │   ├── playback_screen.dart
│   │   │   └── timeline_screen.dart
│   │   ├── alerts/
│   │   │   ├── alert_list_screen.dart
│   │   │   └── alert_detail_screen.dart
│   │   ├── storage/
│   │   │   └── storage_overview_screen.dart
│   │   └── settings/
│   │       ├── settings_screen.dart
│   │       ├── profile_screen.dart
│   │       ├── notification_settings_screen.dart
│   │       └── device_management_screen.dart
│   │
│   ├── widgets/                       # Design System (Zy*)
│   │   ├── buttons/
│   │   │   ├── zy_button.dart
│   │   │   ├── zy_icon_button.dart
│   │   │   └── zy_fab.dart
│   │   ├── inputs/
│   │   │   ├── zy_text_field.dart
│   │   │   ├── zy_search_bar.dart
│   │   │   ├── zy_select.dart
│   │   │   └── zy_switch.dart
│   │   ├── cards/
│   │   │   ├── zy_card.dart
│   │   │   ├── zy_camera_card.dart
│   │   │   ├── zy_stat_card.dart
│   │   │   └── zy_alert_card.dart
│   │   ├── feedback/
│   │   │   ├── zy_badge.dart
│   │   │   ├── zy_toast.dart
│   │   │   ├── zy_skeleton.dart
│   │   │   ├── zy_empty_state.dart
│   │   │   └── zy_loading_indicator.dart
│   │   ├── navigation/
│   │   │   ├── zy_bottom_nav.dart
│   │   │   ├── zy_app_bar.dart
│   │   │   └── zy_drawer.dart
│   │   ├── dialogs/
│   │   │   ├── zy_dialog.dart
│   │   │   ├── zy_confirm_dialog.dart
│   │   │   └── zy_bottom_sheet.dart
│   │   ├── media/
│   │   │   ├── zy_video_player.dart
│   │   │   ├── zy_thumbnail.dart
│   │   │   ├── zy_timeline.dart
│   │   │   └── zy_ptz_controls.dart
│   │   └── layout/
│   │       ├── zy_scaffold.dart
│   │       ├── zy_page_header.dart
│   │       ├── zy_section.dart
│   │       └── zy_responsive_grid.dart
│   │
│   └── router/
│       ├── app_router.dart            # GoRouter config
│       ├── route_names.dart           # Named routes
│       └── guards/
│           ├── auth_guard.dart
│           └── role_guard.dart
│
├── l10n/                              # Internacionalizacao
│   ├── app_pt.arb                     # Portugues (padrao)
│   ├── app_en.arb                     # Ingles
│   └── app_es.arb                     # Espanhol
│
└── gen/                               # Codigo gerado (freezed, json)
    └── .gitkeep
```

---

## 4. Fases de Desenvolvimento

### Fase M1 — Fundacao e Autenticacao (3 semanas)

**Objetivo:** App funcional com login, navegacao e dashboard basico.

| # | Tarefa | Prioridade | Estimativa |
|---|--------|-----------|-----------|
| 1 | Setup projeto Flutter + dependencias | P0 | 1 dia |
| 2 | Configurar arquitetura 5 camadas | P0 | 1 dia |
| 3 | Implementar Design System base (cores, tipografia, espacamento) | P0 | 2 dias |
| 4 | Implementar ZyButton, ZyTextField, ZyCard, ZyBadge | P0 | 2 dias |
| 5 | API Client (Dio) + interceptors + error handling | P0 | 2 dias |
| 6 | Auth flow (login, token storage, refresh, logout) | P0 | 3 dias |
| 7 | GoRouter setup + auth guard + deep links | P0 | 1 dia |
| 8 | Splash screen + onboarding | P1 | 1 dia |
| 9 | Dashboard screen (stats, camera grid, alerts) | P0 | 3 dias |
| 10 | Conectividade monitoring + offline banner | P1 | 1 dia |
| 11 | Tema dark/light + preferencias | P1 | 1 dia |

**Entregaveis:**
- Login funcional com Sanctum
- Dashboard com estatisticas do tenant
- Navegacao principal (bottom nav)
- Design System base implementado
- Tema claro/escuro

---

### Fase M2 — Cameras e Live View (3 semanas)

**Objetivo:** Visualizacao de cameras com streaming HLS em tempo real.

| # | Tarefa | Prioridade | Estimativa |
|---|--------|-----------|-----------|
| 1 | Camera list screen (grid + list view) | P0 | 2 dias |
| 2 | Camera detail screen | P0 | 2 dias |
| 3 | Camera card widget com thumbnail | P0 | 1 dia |
| 4 | HLS player integration (better_player) | P0 | 3 dias |
| 5 | Live view screen (single camera) | P0 | 2 dias |
| 6 | Live view fullscreen mode | P0 | 1 dia |
| 7 | Multi-view screen (2x2 grid) | P1 | 2 dias |
| 8 | Stream start/stop controls | P0 | 1 dia |
| 9 | Adaptive quality switching (ABR) | P1 | 2 dias |
| 10 | Camera search + filters | P1 | 1 dia |
| 11 | Pull-to-refresh + pagination | P1 | 1 dia |

**Entregaveis:**
- Lista de cameras com thumbnails atualizados
- Player HLS com controles
- Multi-view 2x2
- Modo fullscreen
- Qualidade adaptativa

---

### Fase M3 — Push Notifications e Alertas (2 semanas)

**Objetivo:** Notificacoes em tempo real de eventos criticos.

| # | Tarefa | Prioridade | Estimativa |
|---|--------|-----------|-----------|
| 1 | FCM setup (Android + iOS) | P0 | 2 dias |
| 2 | Device registration endpoint | P0 | 1 dia |
| 3 | Push notification handling (foreground + background) | P0 | 2 dias |
| 4 | Deep link routing from notifications | P0 | 1 dia |
| 5 | Alert list screen | P0 | 2 dias |
| 6 | Alert detail screen | P1 | 1 dia |
| 7 | Notification preferences screen | P1 | 1 dia |

**Entregaveis:**
- Push notifications funcionais (camera offline, motion, etc.)
- Deep links para cameras/alertas
- Tela de gerenciamento de notificacoes
- 4 niveis de severidade (P1-P4)

---

### Fase M4 — Gravacoes e Playback (3 semanas)

**Objetivo:** Reproduzir gravacoes com timeline interativa.

| # | Tarefa | Prioridade | Estimativa |
|---|--------|-----------|-----------|
| 1 | Recording list screen (por camera, por data) | P0 | 2 dias |
| 2 | Video player para gravacoes (seek, velocidade) | P0 | 3 dias |
| 3 | Timeline visual interativa | P0 | 3 dias |
| 4 | Clip creation (selecionar trecho) | P1 | 2 dias |
| 5 | Download de gravacoes | P1 | 1 dia |
| 6 | Filtros por data/hora/camera | P0 | 1 dia |
| 7 | Skeleton loading para listas | P1 | 1 dia |

**Entregaveis:**
- Player de playback com controles avancados
- Timeline visual por dia
- Criacao de clips
- Download de trechos

---

### Fase M5 — WebRTC e Features Avancadas (2 semanas)

**Objetivo:** Streaming ultra-low latency e features premium.

| # | Tarefa | Prioridade | Estimativa |
|---|--------|-----------|-----------|
| 1 | WebRTC signaling + connection | P1 | 3 dias |
| 2 | Toggle HLS ↔ WebRTC na live view | P1 | 1 dia |
| 3 | Picture-in-Picture (Android + iOS) | P1 | 2 dias |
| 4 | Background audio/video (notificacao persistente) | P2 | 1 dia |
| 5 | Biometric auth (Face ID / Fingerprint) | P1 | 1 dia |
| 6 | PTZ controls (pan, tilt, zoom) | P2 | 2 dias |

**Entregaveis:**
- WebRTC player operacional
- PiP funcionando em ambas plataformas
- Auth biometrica
- Controles PTZ

---

### Fase M6 — Offline e Sincronizacao (2 semanas)

**Objetivo:** App funcional sem conexao (modo offline).

| # | Tarefa | Prioridade | Estimativa |
|---|--------|-----------|-----------|
| 1 | Cache de dados com Hive (cameras, alertas) | P0 | 2 dias |
| 2 | Sync service (pull/push incremental) | P1 | 3 dias |
| 3 | Conflict resolver | P1 | 1 dia |
| 4 | Offline banner + retry logic | P0 | 1 dia |
| 5 | Cache de thumbnails | P1 | 1 dia |
| 6 | Queue de acoes offline | P1 | 2 dias |

**Entregaveis:**
- Dados de cameras acessiveis offline
- Sincronizacao automatica ao reconectar
- Resolucao de conflitos
- Thumbnails em cache

---

### Fase M7 — Storage e Administracao (1 semana)

**Objetivo:** Visao de armazenamento e gestao do perfil.

| # | Tarefa | Prioridade | Estimativa |
|---|--------|-----------|-----------|
| 1 | Storage overview screen (uso, quota) | P1 | 2 dias |
| 2 | Profile screen (editar nome, avatar, senha) | P1 | 2 dias |
| 3 | Device management (listar, revogar) | P2 | 1 dia |

**Entregaveis:**
- Visao de uso de armazenamento
- Edicao de perfil
- Gerenciamento de dispositivos

---

### Fase M8 — Real-Time e WebSocket (2 semanas)

**Objetivo:** Updates em tempo real via WebSocket.

| # | Tarefa | Prioridade | Estimativa |
|---|--------|-----------|-----------|
| 1 | WebSocket client (Laravel Reverb/Pusher) | P0 | 2 dias |
| 2 | Canal tenant.{id} — status de cameras | P0 | 2 dias |
| 3 | Canal camera.{id} — stream events | P0 | 1 dia |
| 4 | Canal user.{id} — notificacoes pessoais | P1 | 1 dia |
| 5 | Auto-reconnect + exponential backoff | P0 | 1 dia |
| 6 | Update UI em tempo real (badges, status) | P0 | 2 dias |

**Entregaveis:**
- Status de cameras atualiza em tempo real
- Alertas chegam instantaneamente
- Auto-reconexao confiavel

---

### Fase M9 — Polimento e Publicacao (2 semanas)

**Objetivo:** App pronto para producao e publicacao nas stores.

| # | Tarefa | Prioridade | Estimativa |
|---|--------|-----------|-----------|
| 1 | Internacionalizacao (pt-BR, en, es) | P1 | 2 dias |
| 2 | Splash screen nativo (Android + iOS) | P1 | 1 dia |
| 3 | App icon + adaptive icon | P0 | 1 dia |
| 4 | Performance profiling + otimizacao | P0 | 2 dias |
| 5 | Testes unitarios (domain + data) | P0 | 3 dias |
| 6 | Testes de integracao (auth + cameras) | P1 | 2 dias |
| 7 | App Store assets (screenshots, description) | P0 | 1 dia |
| 8 | CI/CD pipeline (GitHub Actions) | P1 | 1 dia |

**Entregaveis:**
- App publicavel na Play Store e App Store
- Performance otimizada (cold start < 2s)
- Testes com coverage > 70%
- CI/CD configurado

---

## 5. Metas de Performance

| Metrica | Alvo |
|---------|------|
| Cold start | < 2s |
| API response (cache) | < 100ms |
| API response (network) | < 500ms |
| HLS start | < 3s |
| WebRTC start | < 1s |
| Push delivery | < 5s |
| Memoria maxima | < 150MB |
| Tamanho APK/IPA | < 50MB |
| Sync offline→online | < 10s |
| FPS rendering | >= 60fps |
| Jank frames | < 1% |

---

## 6. API Endpoints Consumidos

### Autenticacao
- `POST /api/v1/auth/login` — Login
- `POST /api/v1/auth/logout` — Logout
- `GET /api/v1/auth/me` — Dados do usuario
- `POST /api/v1/tokens` — Criar token API
- `GET /api/v1/tokens/abilities` — Listar abilities

### Mobile Especifico
- `GET /api/v1/mobile/dashboard` — Dashboard otimizado
- `GET /api/v1/mobile/sync/status` — Status de sync
- `POST /api/v1/mobile/sync/pull` — Pull incremental
- `POST /api/v1/mobile/sync/push` — Push acoes offline

### Cameras
- `GET /api/v1/cameras` — Listar cameras
- `GET /api/v1/cameras/{id}` — Detalhe
- `GET /api/v1/cameras/{id}/thumbnail` — Thumbnail
- `GET /api/v1/cameras/{id}/stream` — Status stream

### Streaming
- `POST /api/v1/cameras/{id}/stream/start` — Iniciar stream
- `POST /api/v1/cameras/{id}/stream/stop` — Parar stream

### Gravacoes
- `GET /api/v1/recordings` — Listar gravacoes
- `GET /api/v1/recordings/{id}` — Detalhe
- `GET /api/v1/recordings/{id}/stream` — Stream MP4
- `GET /api/v1/recordings/{id}/download` — Download
- `POST /api/v1/recordings/{id}/clip` — Criar clip

### Storage
- `GET /api/v1/storage` — Uso de storage

### Dispositivos Mobile
- `POST /api/v1/user-devices/register` — Registrar device
- `PUT /api/v1/user-devices/{id}/push-token` — Atualizar push token
- `GET /api/v1/user-devices` — Listar devices
- `DELETE /api/v1/user-devices/{id}` — Revogar device

### Perfil
- `GET /api/v1/profile` — Dados do perfil
- `PUT /api/v1/profile` — Atualizar perfil
- `PUT /api/v1/profile/password` — Alterar senha

### WebSocket Channels
- `tenant.{tenantId}` — Eventos do tenant
- `camera.{cameraId}` — Eventos da camera
- `user.{userId}` — Eventos pessoais

---

## 7. Seguranca

### Autenticacao
- Bearer Token via Sanctum (24h expiracao)
- Refresh Token rotation (30d)
- Biometric auth (Face ID / Fingerprint)
- Rate limiting: 60 req/min (foreground), 10 req/min (background)

### Armazenamento
- Tokens em flutter_secure_storage (Keychain iOS / EncryptedSharedPreferences Android)
- Cache local criptografado (Hive boxes encrypted)
- Nenhum dado sensivel em SharedPreferences

### Rede
- TLS 1.3 obrigatorio
- Certificate pinning (producao)
- Headers mobile: X-Device-Id, X-App-Version, X-Platform

### Protecoes
- Jailbreak/root detection (futuro)
- Screenshot prevention em telas sensiveis (futuro)
- Session timeout inativo (15min)

---

## 8. Metodologia CDD (Context-Driven Development)

### Estrutura de Contexto para IA

```
mobile/
├── .cursor/
│   ├── rules/                  # Regras para a IA seguir
│   │   ├── flutter-project.mdc          # Contexto geral
│   │   ├── flutter-architecture.mdc     # Arquitetura 5 camadas
│   │   ├── flutter-design-system.mdc    # Design System Zy*
│   │   ├── flutter-state.mdc            # Riverpod patterns
│   │   ├── flutter-api.mdc              # API client patterns
│   │   ├── flutter-streaming.mdc        # HLS/WebRTC patterns
│   │   ├── flutter-testing.mdc          # Testing standards
│   │   └── flutter-security.mdc         # Security rules
│   │
│   └── skills/                 # Skills para tarefas recorrentes
│       ├── create-screen/               # Criar nova tela
│       ├── create-widget/               # Criar widget Zy*
│       ├── create-feature/              # Feature module completo
│       ├── create-repository/           # Data layer
│       └── create-test/                 # Testes
│
├── docs/cdd/
│   ├── adr/                    # Architecture Decision Records
│   ├── architecture/           # Documentos de arquitetura
│   ├── design-system/          # Especificacoes do Design System
│   ├── api/                    # Documentacao da API consumida
│   └── guidelines/             # Guias de desenvolvimento
```

---

## 9. Dependencias do Backend

### Endpoints que precisam ser criados/ajustados
1. `POST /api/v1/auth/mobile/login` — Login com device info (pode usar o existente)
2. `GET /api/v1/mobile/dashboard` — JA EXISTE
3. `POST/GET /api/v1/mobile/sync/*` — JA EXISTE
4. `POST /api/v1/user-devices/register` — JA EXISTE
5. WebSocket via Reverb — JA CONFIGURADO

### Configuracoes necessarias no backend
- CORS para dominio do app
- Sanctum configurado para tokens mobile
- FCM server key no .env
- Reverb WebSocket acessivel externamente

---

## 10. Riscos e Mitigacoes

| Risco | Impacto | Mitigacao |
|-------|---------|----------|
| Latencia HLS > 3s em 4G | Alto | LL-HLS + ABR + fallback graceful |
| Consumo de dados excessivo | Alto | Quality caps por tipo de rede |
| Battery drain com streaming | Medio | Pause quando background, PiP eficiente |
| WebRTC NAT traversal | Medio | STUN/TURN servers, fallback para HLS |
| Offline sync conflicts | Baixo | Last-write-wins + user notification |
| APK size > 50MB | Medio | Code splitting, deferred components |
| iOS App Store review | Medio | Seguir guidelines, privacy manifest |
