# ADR-0026: Compatibilidade e Implementacao Mobile Completa

## Status
**Aprovado** — 2026-02-06

## Contexto

O ZyraVision necessita de uma implementacao mobile completa que permita operadores e administradores monitorar cameras, receber alertas e gerenciar o sistema remotamente. As ADRs anteriores (0012-0017) definiram aspectos individuais (streaming, push, Flutter, offline). Esta ADR consolida e detalha a implementacao completa da compatibilidade mobile, incluindo arquitetura compartilhada web/mobile.

### ADRs Relacionadas
- ADR-0012: Suporte a Aplicacao Mobile
- ADR-0013: Estrategia de Streaming Mobile
- ADR-0014: Push Notifications
- ADR-0015: Flutter como Framework Mobile
- ADR-0016: Versionamento de APIs
- ADR-0017: Offline Mode
- ADR-0027: Arquitetura de Compartilhamento de Dados

## Decisao

### 1. API Unificada Web + Mobile

A mesma API Laravel serve ambas as plataformas. Diferenciacoes ocorrem via:

- **Headers**: `X-Platform: android|ios|web`, `X-App-Version`, `X-Device-Id`
- **Sparse Fieldsets**: `?fields=id,name,status` para economia de dados mobile
- **Conditional Requests**: `If-Modified-Since`, `ETag` para cache eficiente
- **Compressao**: gzip/brotli obrigatorio para responses mobile

### 2. Endpoints Mobile-Specificos

Alem dos endpoints CRUD padrao, criar endpoints otimizados para mobile:

```
POST   /api/v1/auth/mobile/login          # Login com device fingerprint
POST   /api/v1/auth/mobile/refresh         # Refresh token
POST   /api/v1/auth/mobile/biometric       # Login biometrico
POST   /api/v1/devices/register            # Registrar device
PUT    /api/v1/devices/{id}/push-token     # FCM/APNs token
DELETE /api/v1/devices/{id}                # Revogar device

GET    /api/v1/mobile/dashboard            # Dashboard otimizado (single request)
GET    /api/v1/mobile/cameras/summary      # Lista resumida de cameras
GET    /api/v1/mobile/cameras/{id}/thumbnail  # Thumbnail otimizado
GET    /api/v1/mobile/notifications        # Notificacoes com paginacao
POST   /api/v1/mobile/sync/pull            # Sync incremental
POST   /api/v1/mobile/sync/push            # Enviar acoes offline
```

### 3. Autenticacao Mobile

```
Login -> Token + Refresh Token + Device Registration
       -> Push Token Registration (FCM/APNs)
       -> Biometric enrollment (opcional)
```

- Token de acesso: expira em 24h
- Refresh token: expira em 30 dias, com rotacao
- Device management: maximo 5 devices por usuario
- Revogacao remota de devices

### 4. Compatibilidade de Plataforma

| Feature | iOS | Android | Web Mobile |
|---------|-----|---------|-----------|
| HLS Playback | Nativo | ExoPlayer | hls.js |
| WebRTC | WebRTC framework | WebRTC framework | Nativo |
| Push Notifications | APNs | FCM | Web Push |
| Biometric Auth | Face ID / Touch ID | Fingerprint | N/A |
| Background Sync | Background Fetch | WorkManager | Service Worker |
| Offline Cache | SQLite | SQLite | IndexedDB |
| Picture-in-Picture | Nativo iOS 14+ | Nativo Android 8+ | Chrome |
| Deep Links | Universal Links | App Links | PWA |

### 5. Flutter Implementation Layers

```
lib/
  core/
    api/                # API client, interceptors, retry
      api_client.dart
      auth_interceptor.dart
      cache_interceptor.dart
    auth/               # Auth state, biometric, token management
    cache/              # Local cache (SQLite/Hive)
    connectivity/       # Network state monitoring
    push/               # Push notification handling
    theme/              # Design System tokens (shared from web)
  features/
    dashboard/          # Dashboard screens + widgets
    cameras/            # Camera list, detail, player
    live_view/          # Live streaming view
    recordings/         # Playback, timeline
    alerts/             # Alert list, detail
    settings/           # User settings, device management
    auth/               # Login, biometric, forgot password
  shared/
    widgets/            # Shared UI components (matching web DS)
    models/             # Data models
    providers/          # Riverpod providers
```

### 6. Performance Targets Mobile

| Metrica | Target |
|---------|--------|
| Cold start | < 2s |
| API response (cached) | < 100ms |
| API response (network) | < 500ms |
| Video start (HLS) | < 3s |
| Video start (WebRTC) | < 1s |
| Push delivery | < 5s |
| Memory usage | < 150MB |
| APK/IPA size | < 50MB |
| Offline to online sync | < 10s |

### 7. Testes Mobile

- Unit tests: 80% cobertura (Dart test)
- Widget tests: componentes criticos
- Integration tests: fluxos criticos (login, live view, alerts)
- E2E: Patrol ou integration_test
- Performance: benchmark de FPS e memoria

## Alternativas Consideradas

1. **React Native** — Descartado: Flutter tem melhor performance para video
2. **App nativo (Swift/Kotlin)** — Descartado: custo duplicado de manutencao
3. **PWA only** — Descartado: limitacoes de push, background e video

## Consequencias

### Positivas
- Experiencia consistente web e mobile
- API unificada reduz complexidade
- Flutter permite deploy iOS + Android com codebase unica
- Offline-first garante uso em campo

### Negativas
- Equipe precisa de expertise Flutter
- Manter Design System sincronizado entre Vue e Flutter
- Testes em multiplas plataformas aumentam CI/CD
- Certificacao Apple requer conta developer ($99/ano)

### Riscos
- Drift entre versoes web e mobile
- Limitacoes de video em iOS background
- Tamanho do APK com codecs de video

## Implementacao

### Fase 1 (MVP Mobile — 8 semanas)
- Login + Auth + Device registration
- Lista de cameras com thumbnails
- Live view basico (HLS)
- Push notifications (P1/P2)
- Dashboard resumido

### Fase 2 (Feature Complete — 6 semanas)
- Playback com timeline
- WebRTC (baixa latencia)
- Offline mode basico
- Configuracoes de camera
- Biometric auth

### Fase 3 (Polish — 4 semanas)
- PiP (Picture-in-Picture)
- Deep links
- Widget de home screen
- Performance optimization
- App Store / Play Store submission
