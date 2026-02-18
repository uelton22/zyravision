import 'dart:convert';

import '../config/app_config.dart';

class UrlHelper {
  UrlHelper._();

  /// Constroi URL completa do snapshot da camera.
  /// A API retorna URLs relativas como `/api/cameras/{id}/snapshot`
  /// ou o snapshot_url pode ser null.
  static String snapshotUrl(String cameraId) {
    final base = AppConfig.apiBaseUrl;
    return '$base/cameras/$cameraId/snapshot';
  }

  /// Constroi URL HLS para live view.
  /// O Nginx em :8000 faz proxy /hls/ → MediaMTX:8888 e injeta Basic auth.
  /// Entao o mobile acessa via Nginx sem precisar enviar credenciais.
  static String? hlsUrl(String? mediamtxPath) {
    if (mediamtxPath == null || mediamtxPath.isEmpty) return null;
    final base = AppConfig.streamingBaseUrl;
    return '$base/hls/$mediamtxPath/index.m3u8';
  }

  /// Constroi URL HLS a partir da hls_url retornada pela API.
  /// A API retorna paths como `/hls/cam_xxx/index.m3u8`.
  static String? hlsUrlFromPath(String? hlsPath) {
    if (hlsPath == null || hlsPath.isEmpty) return null;
    final base = AppConfig.streamingBaseUrl;
    // Se ja e URL completa, retorna direto
    if (hlsPath.startsWith('http')) return hlsPath;
    // Remove leading slash se existir
    final path = hlsPath.startsWith('/') ? hlsPath.substring(1) : hlsPath;
    return '$base/$path';
  }

  /// Basic auth header para acesso direto ao MediaMTX (caso necessario).
  /// Normalmente NAO necessario pois o Nginx injeta o header.
  static String get mediamtxBasicAuth {
    final credentials = base64Encode(
      utf8.encode('zyravision:zyravision_api_secret'),
    );
    return 'Basic $credentials';
  }
}
