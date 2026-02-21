import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb, debugPrint;

class AppConfig {
  AppConfig._();

  static const String appName = 'ZyraVision';
  static const String appVersion = '1.0.0';

  // ─── Produção (VPS) ────────────────────────────────────
  // Endpoints em produção — usar com: flutter build apk --dart-define=PRODUCTION=true
  static const String productionWebUrl = 'https://zyravision.com.br';
  static const String productionApiUrl = 'https://api.zyravision.com.br/api/v1';
  static const String productionStreamingUrl = 'https://stream.zyravision.com.br';

  /// true quando compilar com --dart-define=PRODUCTION=true (build de produção).
  /// Padrão: false (ambiente local/dev).
  static bool get useProduction =>
      const bool.fromEnvironment('PRODUCTION', defaultValue: false);

  // ─── Desenvolvimento local ──────────────────────────────
  // DevMode.emulator  → emulador Android (10.0.2.2)
  // DevMode.usbDevice → dispositivo físico via USB (localhost + adb reverse)
  // DevMode.wifi      → dispositivo na rede (IP local)
  static const DevMode _devMode = DevMode.usbDevice;
  static const String _localNetworkIp = '192.168.1.100';

  /// URL base do Laravel web (serve /storage/, landing, etc).
  /// Em produção: zyravision.com.br
  /// Em dev: mesmo host local.
  static String get webBaseUrl {
    if (useProduction) return productionWebUrl;
    return '$_localBaseUrl:8000';
  }

  static String get apiBaseUrl {
    if (useProduction) return productionApiUrl;
    return '$_localBaseUrl:8000/api/v1';
  }

  /// URL base para streaming HLS (live e playback).
  /// Em produção: stream.zyravision.com.br
  /// Em dev: mesmo host da API (Nginx proxy para MediaMTX).
  static String get streamingBaseUrl {
    if (useProduction) return productionStreamingUrl;
    return '$_localBaseUrl:8000';
  }

  static String get _localBaseUrl {
    if (kIsWeb) return 'http://localhost';

    if (Platform.isAndroid) {
      switch (_devMode) {
        case DevMode.emulator:
          return 'http://10.0.2.2';
        case DevMode.usbDevice:
          return 'http://localhost';
        case DevMode.wifi:
          return 'http://$_localNetworkIp';
      }
    }

    // iOS Simulator, macOS, Linux, Windows
    return 'http://localhost';
  }

  /// Log do modo de conexão atual (chamado no startup).
  static void logConfig() {
    debugPrint('╔══════════════════════════════════════════');
    debugPrint('║ ZyraVision - Config');
    debugPrint('║ Mode: ${useProduction ? "PRODUCTION" : "DEV ($_devMode)"}');
    debugPrint('║ Web:  $webBaseUrl');
    debugPrint('║ API:  $apiBaseUrl');
    debugPrint('║ HLS:  $streamingBaseUrl');
    debugPrint('╚══════════════════════════════════════════');
  }

  // ─── Timeouts ──────────────────────────────────────────
  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 30);
  static const Duration sendTimeout = Duration(seconds: 15);

  // ─── Pagination ────────────────────────────────────────
  static const int defaultPageSize = 15;

  // ─── Cache ─────────────────────────────────────────────
  static const Duration cacheTtl = Duration(hours: 1);

  // ─── Retry ─────────────────────────────────────────────
  static const int maxRetries = 3;
  static const Duration initialRetryDelay = Duration(seconds: 1);
}

/// Modo de desenvolvimento para conexao local.
enum DevMode {
  /// Emulador Android: usa 10.0.2.2 (IP especial que aponta para o host)
  emulator,

  /// Dispositivo fisico via USB: usa localhost + adb reverse
  /// Requer: adb reverse tcp:8000 tcp:8000
  usbDevice,

  /// Dispositivo fisico via WiFi: usa IP da rede local
  /// Requer: dispositivo e host na mesma rede WiFi
  wifi,
}
