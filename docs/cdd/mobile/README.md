# Arquitetura Mobile — ZyraVision (Flutter)

## Visao Geral

O app mobile do ZyraVision e construido com Flutter, compartilhando a API REST com o frontend web. Permite operadores monitorarem cameras, receberem alertas e gerenciarem o sistema remotamente.

## Stack Mobile

| Tecnologia | Versao | Uso |
|-----------|--------|-----|
| Flutter | 3.x | Framework cross-platform |
| Dart | 3.x | Linguagem |
| Riverpod | 2.x | State management |
| Dio | 5.x | HTTP client |
| GoRouter | latest | Navegacao |
| Hive | 2.x | Cache local |

## Estrutura de Pastas

```
lib/
├── main.dart
├── app.dart
├── core/
│   ├── api/
│   │   ├── api_client.dart           # Dio client configurado
│   │   ├── api_interceptors.dart     # Auth, retry, cache
│   │   └── api_exceptions.dart       # Error handling
│   ├── auth/
│   │   ├── auth_provider.dart        # Estado de autenticacao
│   │   ├── auth_service.dart         # Login, logout, refresh
│   │   └── biometric_service.dart    # Face ID / Fingerprint
│   ├── cache/
│   │   ├── cache_manager.dart        # Gerenciamento de cache
│   │   └── sync_service.dart         # Sincronizacao offline
│   ├── connectivity/
│   │   └── connectivity_provider.dart # Estado de rede
│   ├── push/
│   │   └── push_service.dart         # FCM/APNs handling
│   └── theme/
│       ├── app_theme.dart            # ThemeData (light/dark)
│       ├── app_colors.dart           # Tokens de cor
│       ├── app_typography.dart       # Tokens tipograficos
│       └── app_spacing.dart          # Tokens de espacamento
│
├── features/
│   ├── auth/
│   │   ├── screens/
│   │   │   ├── login_screen.dart
│   │   │   └── forgot_password_screen.dart
│   │   └── widgets/
│   │       └── login_form.dart
│   ├── dashboard/
│   │   ├── screens/
│   │   │   └── dashboard_screen.dart
│   │   ├── widgets/
│   │   │   ├── stats_card.dart
│   │   │   ├── camera_preview.dart
│   │   │   └── alert_list_widget.dart
│   │   └── providers/
│   │       └── dashboard_provider.dart
│   ├── cameras/
│   │   ├── screens/
│   │   │   ├── camera_list_screen.dart
│   │   │   ├── camera_detail_screen.dart
│   │   │   └── camera_live_screen.dart
│   │   ├── widgets/
│   │   │   ├── camera_card.dart
│   │   │   ├── camera_grid.dart
│   │   │   └── camera_player.dart
│   │   └── providers/
│   │       └── cameras_provider.dart
│   ├── recordings/
│   │   ├── screens/
│   │   │   └── playback_screen.dart
│   │   └── widgets/
│   │       └── timeline_widget.dart
│   ├── alerts/
│   │   ├── screens/
│   │   │   ├── alerts_screen.dart
│   │   │   └── alert_detail_screen.dart
│   │   └── providers/
│   │       └── alerts_provider.dart
│   └── settings/
│       ├── screens/
│       │   ├── settings_screen.dart
│       │   ├── profile_screen.dart
│       │   └── devices_screen.dart
│       └── providers/
│           └── settings_provider.dart
│
├── shared/
│   ├── models/
│   │   ├── user.dart
│   │   ├── camera.dart
│   │   ├── alert.dart
│   │   ├── recording.dart
│   │   └── notification.dart
│   ├── widgets/
│   │   ├── zy_button.dart            # Design System button
│   │   ├── zy_input.dart             # Design System input
│   │   ├── zy_card.dart              # Design System card
│   │   ├── zy_badge.dart             # Design System badge
│   │   ├── zy_alert.dart             # Design System alert
│   │   ├── loading_indicator.dart
│   │   ├── empty_state.dart
│   │   └── error_widget.dart
│   └── utils/
│       ├── date_formatter.dart
│       ├── validators.dart
│       └── constants.dart
│
└── router/
    └── app_router.dart               # GoRouter config
```

## Fluxos Principais

### 1. Login
```
Login Screen → API /auth/mobile/login → Token + User
             → Register Device + Push Token
             → Navigate to Dashboard
```

### 2. Dashboard
```
Dashboard Screen → API /mobile/dashboard (single request)
                → Render Stats + Camera Previews + Alerts
                → WebSocket connection for real-time updates
```

### 3. Live View
```
Camera List → Select Camera → API /streams/{id}/hls → URL
            → HLS Player (baixa latencia)
            → Ou WebRTC (ultra baixa latencia)
```

### 4. Push Notification
```
FCM/APNs → Push Service → Parse payload
         → Local notification
         → Tap → Deep link → Camera/Alert screen
```

## Design System Mobile

Os componentes Flutter devem espelhar visualmente os componentes Vue:

| Vue Component | Flutter Widget |
|--------------|----------------|
| ZyButton | ZyButton (Flutter) |
| ZyInput | ZyInput (Flutter) |
| ZyCard | ZyCard (Flutter) |
| ZyBadge | ZyBadge (Flutter) |
| ZyAlert | ZyAlert (Flutter) |

Cores, tipografia e espacamentos sao derivados do mesmo `design-tokens.json`.

## Roadmap Mobile

### MVP (8 semanas)
- Login + Auth
- Dashboard
- Lista de cameras
- Live view (HLS)
- Push notifications

### V1.1 (6 semanas)
- Playback + Timeline
- WebRTC
- Offline mode basico
- Biometric auth

### V1.2 (4 semanas)
- PiP (Picture-in-Picture)
- Deep links
- Home screen widget
- App Store submission
