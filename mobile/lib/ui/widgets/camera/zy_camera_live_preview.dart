import 'package:flutter/material.dart';
import 'package:media_kit/media_kit.dart';
import 'package:media_kit_video/media_kit_video.dart';

import '../../../core/utils/url_helper.dart';

/// Widget de preview ao vivo de uma camera usando HLS.
/// Mostra o stream em um mini player sem controles.
/// Gerencia o ciclo de vida do Player automaticamente.
class ZyCameraLivePreview extends StatefulWidget {
  final String? hlsPath;
  final String? mediamtxPath;
  final bool isOnline;
  final double? height;
  final BoxFit fit;

  const ZyCameraLivePreview({
    super.key,
    this.hlsPath,
    this.mediamtxPath,
    this.isOnline = false,
    this.height,
    this.fit = BoxFit.cover,
  });

  @override
  State<ZyCameraLivePreview> createState() => _ZyCameraLivePreviewState();
}

class _ZyCameraLivePreviewState extends State<ZyCameraLivePreview> {
  Player? _player;
  VideoController? _videoController;
  bool _isBuffering = true;
  bool _hasError = false;
  bool _initialized = false;

  String? get _hlsUrl {
    if (widget.hlsPath != null) {
      return UrlHelper.hlsUrlFromPath(widget.hlsPath);
    }
    if (widget.mediamtxPath != null) {
      return UrlHelper.hlsUrl(widget.mediamtxPath);
    }
    return null;
  }

  bool get _canPlay => widget.isOnline && _hlsUrl != null;

  @override
  void initState() {
    super.initState();
    if (_canPlay) {
      _initPlayer();
    }
  }

  @override
  void didUpdateWidget(ZyCameraLivePreview oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.hlsPath != widget.hlsPath ||
        oldWidget.mediamtxPath != widget.mediamtxPath ||
        oldWidget.isOnline != widget.isOnline) {
      if (_canPlay && !_initialized) {
        _initPlayer();
      } else if (!_canPlay && _initialized) {
        _disposePlayer();
      }
    }
  }

  void _initPlayer() {
    _player = Player(
      configuration: const PlayerConfiguration(
        logLevel: MPVLogLevel.error,
      ),
    );
    _videoController = VideoController(
      _player!,
      configuration: const VideoControllerConfiguration(
        enableHardwareAcceleration: false,
      ),
    );

    _player!.stream.buffering.listen((buffering) {
      if (mounted) setState(() => _isBuffering = buffering);
    });

    _player!.stream.error.listen((error) {
      if (mounted && error.isNotEmpty) {
        setState(() => _hasError = true);
      }
    });

    _initialized = true;

    // Aguarda o widget ser construido para a surface estar disponivel
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      if (!mounted) return;
      await Future.delayed(const Duration(milliseconds: 600));
      if (mounted && _player != null) {
        final url = _hlsUrl!;
        debugPrint('[LivePreview] Opening: $url');
        await _player!.open(Media(url));
        // Volume zero para preview silencioso
        await _player!.setVolume(0);
      }
    });
  }

  void _disposePlayer() {
    _player?.dispose();
    _player = null;
    _videoController = null;
    _initialized = false;
  }

  @override
  void dispose() {
    _disposePlayer();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    if (!_canPlay || _videoController == null) {
      return _placeholder(theme);
    }

    return SizedBox(
      height: widget.height,
      width: double.infinity,
      child: Stack(
        fit: StackFit.expand,
        children: [
          Video(
            controller: _videoController!,
            controls: NoVideoControls,
            fill: Colors.black,
            fit: widget.fit,
          ),
          if (_isBuffering)
            Container(
              color: Colors.black54,
              child: const Center(
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.white54,
                  ),
                ),
              ),
            ),
          if (_hasError) _placeholder(theme),
        ],
      ),
    );
  }

  Widget _placeholder(ThemeData theme) {
    return Container(
      height: widget.height,
      width: double.infinity,
      color: theme.colorScheme.surfaceContainerHighest,
      child: Center(
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
            if (!widget.isOnline) ...[
              const SizedBox(height: 4),
              Text(
                'Offline',
                style: TextStyle(
                  fontSize: 10,
                  color: theme.colorScheme.outline,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
