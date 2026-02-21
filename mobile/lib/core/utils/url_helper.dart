import 'dart:convert';

import '../config/app_config.dart';

class UrlHelper {
  UrlHelper._();

  /// Constrói URL completa do snapshot da camera.
  /// Usa o endpoint de API /cameras/{id}/snapshot (retorna imagem com auth).
  static String snapshotUrl(String cameraId) {
    final base = AppConfig.apiBaseUrl;
    return '$base/cameras/$cameraId/snapshot';
  }

  /// Constrói URL completa para arquivos de storage (/storage/...).
  /// Em produção: zyravision.com.br/storage/... (Laravel web serve static).
  /// Em dev: localhost:8000/storage/...
  static String? storageUrl(String? path) {
    if (path == null || path.isEmpty) return null;
    if (path.startsWith('http')) return path;
    final base = AppConfig.webBaseUrl;
    final cleanPath = path.startsWith('/') ? path.substring(1) : path;
    return '$base/$cleanPath';
  }

  /// Constrói URL HLS para live view.
  /// Em produção: stream.zyravision.com.br/hls/{path}/index.m3u8
  /// Em dev: localhost:8000/hls/{path}/index.m3u8 (Nginx proxy → MediaMTX).
  static String? hlsUrl(String? mediamtxPath) {
    if (mediamtxPath == null || mediamtxPath.isEmpty) return null;
    final base = AppConfig.streamingBaseUrl;
    return '$base/hls/$mediamtxPath/index.m3u8';
  }

  /// Constrói URL HLS a partir do path retornado pela API (/hls/cam_xxx/index.m3u8).
  static String? hlsUrlFromPath(String? hlsPath) {
    if (hlsPath == null || hlsPath.isEmpty) return null;
    final base = AppConfig.streamingBaseUrl;
    if (hlsPath.startsWith('http')) return hlsPath;
    final path = hlsPath.startsWith('/') ? hlsPath.substring(1) : hlsPath;
    return '$base/$path';
  }

  /// Basic auth header para acesso direto ao MediaMTX (caso necessário).
  static String get mediamtxBasicAuth {
    final credentials = base64Encode(
      utf8.encode('zyravision:zyravision_api_secret'),
    );
    return 'Basic $credentials';
  }
}
