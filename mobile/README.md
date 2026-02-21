# ZyraVision Mobile

App Flutter do ZyraVision — monitoramento CFTV (câmeras, live view HLS, gravações, dashboard).

## Ambientes

O app usa `lib/core/config/app_config.dart` para API e streaming:

| Ambiente   | API                          | Streaming                      |
|-----------|------------------------------|--------------------------------|
| **Dev**   | `http://localhost:8000/api/v1` (ou emulador/WiFi) | `http://localhost:8000` |
| **Produção** | `https://api.zyravision.com.br/api/v1` | `https://stream.zyravision.com.br` |

- **Dev (padrão):** `flutter run` ou `flutter build apk` → aponta para máquina local (use `adb reverse tcp:8000 tcp:8000` no dispositivo físico).
- **Produção:** compilar com `--dart-define=PRODUCTION=true` para usar os endpoints da VPS.

## Build APK para produção (teste no dispositivo)

```bash
cd mobile
flutter pub get
flutter build apk --dart-define=PRODUCTION=true
```

O APK fica em: `build/app/outputs/flutter-apk/app-release.apk`.

Instalar no dispositivo conectado via USB (usa o APK já gerado com PRODUCTION=true):

```bash
flutter install
# ou manualmente:
adb install build/app/outputs/flutter-apk/app-release.apk
```

## Verificar endpoints em produção

Antes do build, confirme que os endpoints respondem:

- https://zyravision.com.br/ (landing)
- https://api.zyravision.com.br/ (API)
- https://stream.zyravision.com.br/ (streaming HLS)

No primeiro login no app (build produção), use um usuário válido do tenant em produção; o app envia credenciais para `api.zyravision.com.br` e recebe o token Sanctum.

## Getting Started

- [Flutter documentation](https://docs.flutter.dev/)
- Design System / regras: `.cursor/rules/flutter-*.mdc`
