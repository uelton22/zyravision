import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb, debugPrint;

class AppConfig {
  AppConfig._();

  static const String appName = 'ZyraVision';
  static const String appVersion = '1.0.0';

  // ─── API URLs ──────────────────────────────────────────
  // Producao: altere para o dominio real quando fizer deploy
  static const String productionApiUrl = 'https://api.zyravision.com.br/api/v1';
  static const String productionStreamingUrl = 'https://stream.zyravision.com.br';

  // ─── Modo de desenvolvimento ─────────────────────────────
  // Escolha o modo adequado para seu ambiente de teste:
  //
  // DevMode.emulator  → usa 10.0.2.2 (emulador Android)
  // DevMode.usbDevice → usa localhost (dispositivo fisico via adb reverse)
  // DevMode.wifi      → usa IP da rede local (dispositivo fisico via WiFi)
  static const DevMode _devMode = DevMode.usbDevice;

  // IP da maquina na rede local (usar com DevMode.wifi)
  // Descubra com: ifconfig | grep "inet " (macOS/Linux)
  static const String _localNetworkIp = '192.168.1.100';

  static String get apiBaseUrl {
    if (_useProduction) return productionApiUrl;
    return '$_localBaseUrl:8000/api/v1';
  }

  /// URL base para streaming HLS.
  /// Em dev, usa a mesma porta do Nginx (8000) que faz proxy para o MediaMTX
  /// e injeta auth automaticamente. NAO acessar MediaMTX diretamente (8888).
  static String get streamingBaseUrl {
    if (_useProduction) return productionStreamingUrl;
    return '$_localBaseUrl:8000';
  }

  // Mude para true quando quiser apontar para producao
  static const bool _useProduction = false;

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

  /// Log do modo de conexao atual (chamado no startup)
  static void logConfig() {
    debugPrint('╔══════════════════════════════════════════');
    debugPrint('║ ZyraVision - Config');
    debugPrint('║ Mode: ${_useProduction ? "PRODUCTION" : "DEV ($_devMode)"}');
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
