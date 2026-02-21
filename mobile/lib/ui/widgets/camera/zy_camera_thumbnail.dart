import 'dart:async';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/utils/url_helper.dart';

/// Widget que carrega e exibe o snapshot da camera com autenticacao Bearer.
/// Usa a snapshot_url da camera (campo retornado pela API).
/// Atualiza automaticamente a cada [refreshInterval] para cameras online.
class ZyCameraThumbnail extends ConsumerStatefulWidget {
  final String cameraId;
  final String? snapshotUrl;
  final bool isOnline;
  final double? height;
  final BoxFit fit;
  final Duration refreshInterval;

  const ZyCameraThumbnail({
    super.key,
    required this.cameraId,
    this.snapshotUrl,
    this.isOnline = false,
    this.height,
    this.fit = BoxFit.cover,
    this.refreshInterval = const Duration(seconds: 15),
  });

  @override
  ConsumerState<ZyCameraThumbnail> createState() => _ZyCameraThumbnailState();
}

class _ZyCameraThumbnailState extends ConsumerState<ZyCameraThumbnail> {
  Uint8List? _imageData;
  bool _isLoading = false;
  Timer? _refreshTimer;

  /// Verifica se a snapshot_url aponta para uma imagem valida
  bool get _hasValidSnapshotUrl =>
      widget.snapshotUrl != null &&
      widget.snapshotUrl!.isNotEmpty &&
      !widget.snapshotUrl!.endsWith('/thumbnail');

  @override
  void initState() {
    super.initState();
    if (_hasValidSnapshotUrl) {
      _loadSnapshot();
      if (widget.isOnline) {
        _startRefreshTimer();
      }
    }
  }

  @override
  void didUpdateWidget(ZyCameraThumbnail oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.cameraId != widget.cameraId ||
        oldWidget.snapshotUrl != widget.snapshotUrl) {
      if (_hasValidSnapshotUrl) {
        _loadSnapshot();
      }
    }
    if (widget.isOnline && _refreshTimer == null && _hasValidSnapshotUrl) {
      _startRefreshTimer();
    } else if (!widget.isOnline || !_hasValidSnapshotUrl) {
      _refreshTimer?.cancel();
      _refreshTimer = null;
    }
  }

  void _startRefreshTimer() {
    _refreshTimer?.cancel();
    _refreshTimer = Timer.periodic(widget.refreshInterval, (_) {
      if (mounted && widget.isOnline && _hasValidSnapshotUrl) {
        _loadSnapshot();
      }
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _loadSnapshot() async {
    if (!mounted || !_hasValidSnapshotUrl) return;

    setState(() => _isLoading = _imageData == null);

    try {
      final client = ref.read(apiClientProvider);
      final snapshotUrl = widget.snapshotUrl!;

      // Em produção, /storage/ paths são servidos pelo Laravel web (zyravision.com.br),
      // não pelo subdomínio API. Usamos UrlHelper.storageUrl() que resolve para webBaseUrl.
      // Para paths de API (/api/...), usamos o Dio do ApiClient com auth.
      String fullUrl;
      if (snapshotUrl.startsWith('http')) {
        fullUrl = snapshotUrl;
      } else if (snapshotUrl.startsWith('/storage/') || !snapshotUrl.startsWith('/api/')) {
        final resolved = UrlHelper.storageUrl(snapshotUrl);
        if (resolved == null) return;
        fullUrl = resolved;
      } else {
        fullUrl = snapshotUrl;
      }

      debugPrint(
          '[Thumbnail] Loading: snapshotUrl=$snapshotUrl → fullUrl=$fullUrl');

      // /storage/ paths don't need Bearer auth — fetch directly.
      // /api/ paths use the authenticated Dio client.
      final Response<List<int>> response;
      if (fullUrl.startsWith('http') && !fullUrl.contains('/api/')) {
        final plainDio = Dio();
        response = await plainDio.get<List<int>>(
          fullUrl,
          options: Options(responseType: ResponseType.bytes),
        );
        plainDio.close();
      } else {
        response = await client.dio.get<List<int>>(
          fullUrl,
          options: Options(responseType: ResponseType.bytes),
        );
      }

      if (!mounted || response.data == null || response.data!.isEmpty) return;

      final bytes = Uint8List.fromList(response.data!);

      // Verifica se os bytes sao uma imagem valida (JPEG, PNG, GIF, WebP)
      if (_isValidImageData(bytes)) {
        debugPrint('[Thumbnail] Image loaded OK (${bytes.length} bytes)');
        setState(() {
          _imageData = bytes;
          _isLoading = false;
        });
      } else {
        // Mostra os primeiros bytes para diagnostico
        final preview = bytes.length > 100
            ? String.fromCharCodes(bytes.take(100))
            : String.fromCharCodes(bytes);
        debugPrint(
            '[Thumbnail] Nao e imagem valida (${bytes.length} bytes). '
            'Primeiros bytes: ${bytes.take(8).toList()}. '
            'Preview: $preview');
        setState(() => _isLoading = false);
      }
    } catch (e) {
      debugPrint('[Thumbnail] Erro ao carregar snapshot: $e');
      if (mounted) setState(() => _isLoading = false);
    }
  }

  /// Verifica magic bytes para formatos de imagem comuns
  bool _isValidImageData(Uint8List data) {
    if (data.length < 4) return false;

    // JPEG: FF D8 FF
    if (data[0] == 0xFF && data[1] == 0xD8 && data[2] == 0xFF) {
      return true;
    }

    // PNG: 89 50 4E 47
    if (data[0] == 0x89 &&
        data[1] == 0x50 &&
        data[2] == 0x4E &&
        data[3] == 0x47) {
      return true;
    }

    // GIF: 47 49 46
    if (data[0] == 0x47 && data[1] == 0x49 && data[2] == 0x46) {
      return true;
    }

    // WebP: 52 49 46 46 ... 57 45 42 50
    if (data[0] == 0x52 && data[1] == 0x49 && data[2] == 0x46 && data[3] == 0x46) {
      if (data.length > 11 &&
          data[8] == 0x57 &&
          data[9] == 0x45 &&
          data[10] == 0x42 &&
          data[11] == 0x50) {
        return true;
      }
    }

    // BMP: 42 4D
    if (data[0] == 0x42 && data[1] == 0x4D) {
      return true;
    }

    return false;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      height: widget.height,
      width: double.infinity,
      color: theme.colorScheme.surfaceContainerHighest,
      child: _buildContent(theme),
    );
  }

  Widget _buildContent(ThemeData theme) {
    if (_imageData != null) {
      return Image.memory(
        _imageData!,
        fit: widget.fit,
        width: double.infinity,
        height: widget.height,
        gaplessPlayback: true,
        errorBuilder: (_, error, __) {
          debugPrint('[Thumbnail] Image.memory error: $error');
          return _placeholder(theme);
        },
      );
    }

    if (_isLoading) {
      return Center(
        child: SizedBox(
          width: 24,
          height: 24,
          child: CircularProgressIndicator(
            strokeWidth: 2,
            color: theme.colorScheme.outline.withValues(alpha: 0.5),
          ),
        ),
      );
    }

    return _placeholder(theme);
  }

  Widget _placeholder(ThemeData theme) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            widget.isOnline
                ? Icons.videocam_outlined
                : Icons.videocam_off_outlined,
            size: 32,
            color: theme.colorScheme.outline,
          ),
          if (widget.isOnline && !_hasValidSnapshotUrl) ...[
            const SizedBox(height: 4),
            Text(
              'Sem preview',
              style: TextStyle(
                fontSize: 10,
                color: theme.colorScheme.outline,
              ),
            ),
          ],
        ],
      ),
    );
  }
}
