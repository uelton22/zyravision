import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;

class AppConfig {
  AppConfig._();

  static const String appName = 'ZyraVision';
  static const String appVersion = '1.0.0';

  // ─── API URLs ──────────────────────────────────────────
  // Producao: altere para o dominio real quando fizer deploy
  // Ex: 'https://api.zyravision.com.br/api/v1'
  static const String productionApiUrl = 'https://api.zyravision.com.br/api/v1';
  static const String productionStreamingUrl = 'https://stream.zyravision.com.br';

  // Desenvolvimento: detecta automaticamente o IP correto
  // - Emulador Android: 10.0.2.2 (IP especial que aponta para o host)
  // - iOS Simulator / macOS / Web: localhost
  static String get apiBaseUrl {
    if (_useProduction) return productionApiUrl;
    return '$_localBaseUrl:8000/api/v1';
  }

  static String get streamingBaseUrl {
    if (_useProduction) return productionStreamingUrl;
    return '$_localBaseUrl:8888';
  }

  // Mude para true quando quiser apontar para producao
  static const bool _useProduction = false;

  // Detecta o IP correto para desenvolvimento local
  static String get _localBaseUrl {
    if (kIsWeb) return 'http://localhost';
    if (Platform.isAndroid) return 'http://10.0.2.2';
    // iOS Simulator, macOS, Linux, Windows
    return 'http://localhost';
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
