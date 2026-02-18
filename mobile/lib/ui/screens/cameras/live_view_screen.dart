import 'dart:io' show Platform;

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:media_kit/media_kit.dart';
import 'package:media_kit_video/media_kit_video.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../core/utils/url_helper.dart';
import '../../../state/camera/camera_providers.dart';
import '../../widgets/feedback/zy_badge.dart';

class LiveViewScreen extends ConsumerStatefulWidget {
  final String cameraId;
  final String cameraName;

  const LiveViewScreen({
    super.key,
    required this.cameraId,
    required this.cameraName,
  });

  @override
  ConsumerState<LiveViewScreen> createState() => _LiveViewScreenState();
}

class _LiveViewScreenState extends ConsumerState<LiveViewScreen> {
  late final Player _player;
  late final VideoController _videoController;
  bool _isFullscreen = false;
  bool _controlsVisible = true;
  bool _isBuffering = true;
  bool _isPlaying = false;
  int _videoWidth = 0;
  int _videoHeight = 0;
  String? _errorMessage;
  bool _streamInitialized = false;
  bool _isEmulator = false;
  String? _hlsUrl;

  @override
  void initState() {
    super.initState();
    _detectEmulator();
    _player = Player(
      configuration: const PlayerConfiguration(
        logLevel: MPVLogLevel.v,
      ),
    );
    _videoController = VideoController(
      _player,
      configuration: const VideoControllerConfiguration(
        // Desabilita HW acceleration para evitar problemas com
        // mediacodec surface/native_window timing no Android.
        // Software decoding e mais lento mas garante renderizacao.
        enableHardwareAcceleration: false,
      ),
    );

    _player.stream.buffering.listen((buffering) {
      if (mounted) {
        setState(() => _isBuffering = buffering);
        debugPrint('[LiveView] Buffering: $buffering');
      }
    });

    _player.stream.playing.listen((playing) {
      if (mounted) {
        setState(() => _isPlaying = playing);
        debugPrint('[LiveView] Playing: $playing');
      }
    });

    _player.stream.width.listen((width) {
      if (mounted && width != null) {
        setState(() => _videoWidth = width);
        debugPrint('[LiveView] Video width: $width');
      }
    });

    _player.stream.height.listen((height) {
      if (mounted && height != null) {
        setState(() => _videoHeight = height);
        debugPrint('[LiveView] Video height: $height');
      }
    });

    _player.stream.error.listen((error) {
      debugPrint('[LiveView] Player error: $error');
      if (mounted && error.isNotEmpty) {
        setState(() => _errorMessage = error);
      }
    });

    _player.stream.log.listen((log) {
      debugPrint('[LiveView][mpv] ${log.prefix}: ${log.text}');
    });
  }

  void _detectEmulator() {
    if (Platform.isAndroid) {
      // media_kit detecta automaticamente e forca SW rendering,
      // mas EGL context pode falhar no emulador
      _isEmulator = true;
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_streamInitialized) {
      _streamInitialized = true;
      // Aguarda o widget tree ser construido e a surface nativa criada.
      // O Video widget precisa estar no tree para criar a surface Android.
      // addPostFrameCallback garante que o build() ja executou.
      // O delay extra de 500ms da tempo para a surface nativa ser inicializada,
      // evitando "h264_mediacodec: Both surface and native_window are NULL".
      WidgetsBinding.instance.addPostFrameCallback((_) async {
        if (!mounted) return;
        // Aguarda a surface nativa ser inicializada
        await Future.delayed(const Duration(milliseconds: 800));
        if (mounted) _initStream();
      });
    }
  }

  Future<void> _initStream() async {
    try {
      final useCase = ref.read(getCameraDetailUseCaseProvider);
      final camera = await useCase(widget.cameraId);

      debugPrint('[LiveView] Camera: ${camera.name}, '
          'online=${camera.isOnline}, '
          'stream=${camera.stream?.status}, '
          'mediamtxPath=${camera.stream?.mediamtxPath}, '
          'hlsFromApi=${camera.stream?.urls?.hls}');

      // Tenta construir URL HLS
      String? hlsUrl;

      // 1. Tenta usar urls.hls do active_stream (URL retornada pela API)
      if (camera.stream?.urls?.hls != null) {
        hlsUrl = UrlHelper.hlsUrlFromPath(camera.stream!.urls!.hls);
        debugPrint('[LiveView] HLS URL from API urls.hls: $hlsUrl');
      }

      // 2. Se nao tem urls.hls, constroi a partir do mediamtx_path
      if (hlsUrl == null && camera.stream?.mediamtxPath != null) {
        hlsUrl = UrlHelper.hlsUrl(camera.stream!.mediamtxPath);
        debugPrint('[LiveView] HLS URL from mediamtxPath: $hlsUrl');
      }

      if (hlsUrl != null && mounted) {
        setState(() => _hlsUrl = hlsUrl);

        // Verifica se o M3U8 esta acessivel antes de abrir no player
        try {
          final testDio = Dio();
          final m3u8Response = await testDio.get<String>(hlsUrl);
          debugPrint('[LiveView] M3U8 status: ${m3u8Response.statusCode}');
          debugPrint(
              '[LiveView] M3U8 content (${m3u8Response.data?.length ?? 0} chars): '
              '${m3u8Response.data?.substring(0, (m3u8Response.data?.length ?? 0).clamp(0, 200))}');
          testDio.close();
        } catch (e) {
          debugPrint('[LiveView] M3U8 test fetch error: $e');
        }

        debugPrint('[LiveView] Opening HLS: $hlsUrl');
        await _player.open(Media(hlsUrl));
        debugPrint('[LiveView] Player.open() completed');
      } else if (mounted) {
        setState(() => _errorMessage =
            'Stream HLS nao disponivel.\nVerifique se a camera esta online e o stream ativo.');
      }
    } catch (e) {
      debugPrint('[LiveView] Error: $e');
      if (mounted) {
        setState(() => _errorMessage = 'Erro ao carregar stream: $e');
      }
    }
  }

  void _toggleFullscreen() {
    setState(() => _isFullscreen = !_isFullscreen);
    if (_isFullscreen) {
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
      SystemChrome.setPreferredOrientations([
        DeviceOrientation.landscapeLeft,
        DeviceOrientation.landscapeRight,
      ]);
    } else {
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
      SystemChrome.setPreferredOrientations([
        DeviceOrientation.portraitUp,
      ]);
    }
  }

  void _toggleControls() {
    setState(() => _controlsVisible = !_controlsVisible);
  }

  @override
  void dispose() {
    _player.dispose();
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_isFullscreen) {
      return _buildFullscreenPlayer();
    }

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(widget.cameraName),
        actions: [
          IconButton(
            icon: const Icon(Icons.fullscreen_rounded),
            tooltip: 'Tela cheia',
            onPressed: _toggleFullscreen,
          ),
        ],
      ),
      body: Column(
        children: [
          // Video player
          AspectRatio(
            aspectRatio: 16 / 9,
            child: _buildVideoPlayer(),
          ),
          // Info panel
          Expanded(
            child: _buildInfoPanel(),
          ),
        ],
      ),
    );
  }

  Widget _buildFullscreenPlayer() {
    return Scaffold(
      backgroundColor: Colors.black,
      body: GestureDetector(
        onTap: _toggleControls,
        child: Stack(
          fit: StackFit.expand,
          children: [
            _buildVideoPlayer(),
            if (_controlsVisible)
              _FullscreenOverlay(
                cameraName: widget.cameraName,
                onExitFullscreen: _toggleFullscreen,
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildVideoPlayer() {
    if (_errorMessage != null) {
      return Container(
        color: Colors.black,
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(ZySpacing.xl),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.error_outline,
                    color: ZyColors.danger, size: 48),
                const SizedBox(height: ZySpacing.md),
                Text(
                  _errorMessage!,
                  style: const TextStyle(color: Colors.white70),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: ZySpacing.base),
                OutlinedButton(
                  onPressed: () {
                    setState(() => _errorMessage = null);
                    _initStream();
                  },
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: const BorderSide(color: Colors.white38),
                  ),
                  child: const Text('Tentar novamente'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Stack(
      alignment: Alignment.center,
      children: [
        Video(
          controller: _videoController,
          controls: NoVideoControls,
          fill: Colors.black,
        ),
        if (_isBuffering)
          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const CircularProgressIndicator(
                color: Colors.white,
                strokeWidth: 2,
              ),
              const SizedBox(height: ZySpacing.sm),
              Text(
                _isPlaying ? 'Carregando...' : 'Conectando ao stream...',
                style: const TextStyle(
                  color: Colors.white54,
                  fontSize: 12,
                ),
              ),
            ],
          ),
        // Mostra info de debug quando nao ha video renderizando
        if (!_isBuffering && _isPlaying && _videoWidth == 0)
          Container(
            padding: const EdgeInsets.all(ZySpacing.base),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.info_outline, color: Colors.white38, size: 32),
                const SizedBox(height: ZySpacing.sm),
                const Text(
                  'Stream conectado',
                  style: TextStyle(color: Colors.white70, fontSize: 14),
                ),
                if (_isEmulator) ...[
                  const SizedBox(height: ZySpacing.xs),
                  const Text(
                    'O emulador Android tem limitacoes de\n'
                    'renderizacao de video (OpenGL).\n'
                    'Teste em um dispositivo fisico.',
                    style: TextStyle(color: Colors.white38, fontSize: 11),
                    textAlign: TextAlign.center,
                  ),
                ],
              ],
            ),
          ),
        // LIVE badge
        Positioned(
          top: ZySpacing.sm,
          left: ZySpacing.sm,
          child: ZyBadge(
            label: _isPlaying ? 'LIVE' : 'CONECTANDO',
            variant:
                _isPlaying ? ZyBadgeVariant.danger : ZyBadgeVariant.warning,
            icon: Icons.fiber_manual_record,
          ),
        ),
        // Resolucao do video
        if (_videoWidth > 0 && _videoHeight > 0)
          Positioned(
            top: ZySpacing.sm,
            right: ZySpacing.sm,
            child: Container(
              padding: const EdgeInsets.symmetric(
                horizontal: 6,
                vertical: 2,
              ),
              decoration: BoxDecoration(
                color: Colors.black54,
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                '${_videoWidth}x$_videoHeight',
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 10,
                  fontFamily: 'monospace',
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildInfoPanel() {
    final cameraAsync = ref.watch(cameraDetailProvider(widget.cameraId));

    return Container(
      color: Theme.of(context).scaffoldBackgroundColor,
      child: cameraAsync.when(
        data: (camera) => ListView(
          padding: const EdgeInsets.all(ZySpacing.base),
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        camera.name,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: ZySpacing.xs),
                      Row(
                        children: [
                          Container(
                            width: 8,
                            height: 8,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: camera.isOnline
                                  ? ZyColors.success
                                  : ZyColors.danger,
                            ),
                          ),
                          const SizedBox(width: ZySpacing.xs),
                          Text(
                            camera.isOnline ? 'Online' : 'Offline',
                            style:
                                Theme.of(context).textTheme.bodySmall?.copyWith(
                                      color:
                                          Theme.of(context).colorScheme.outline,
                                    ),
                          ),
                          if (camera.isRecording) ...[
                            const SizedBox(width: ZySpacing.md),
                            const ZyBadge(
                              label: 'REC',
                              variant: ZyBadgeVariant.danger,
                              size: ZyBadgeSize.sm,
                              pulse: true,
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.fullscreen_rounded),
                  tooltip: 'Tela cheia',
                  onPressed: _toggleFullscreen,
                ),
              ],
            ),
            const SizedBox(height: ZySpacing.base),
            const Divider(),
            const SizedBox(height: ZySpacing.sm),
            if (camera.resolution != null)
              _DetailRow('Resolucao', camera.resolution!),
            if (camera.fps != null) _DetailRow('FPS', '${camera.fps}'),
            if (camera.codec != null) _DetailRow('Codec', camera.codec!),
            if (camera.stream?.status != null)
              _DetailRow('Stream', camera.stream!.status),
            if (_hlsUrl != null) ...[
              const SizedBox(height: ZySpacing.sm),
              _DetailRow('HLS URL', _hlsUrl!.split('/').last),
            ],
            _DetailRow(
                'Player',
                _isPlaying
                    ? 'Reproduzindo'
                    : (_isBuffering ? 'Carregando...' : 'Parado')),
            if (_videoWidth > 0)
              _DetailRow(
                  'Resolucao do video', '${_videoWidth}x$_videoHeight'),
          ],
        ),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (_, __) => const SizedBox.shrink(),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DetailRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: ZySpacing.sm),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Theme.of(context).colorScheme.outline,
                ),
          ),
          Text(
            value,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w500,
                ),
          ),
        ],
      ),
    );
  }
}

class _FullscreenOverlay extends StatelessWidget {
  final String cameraName;
  final VoidCallback onExitFullscreen;

  const _FullscreenOverlay({
    required this.cameraName,
    required this.onExitFullscreen,
  });

  @override
  Widget build(BuildContext context) {
    return AnimatedOpacity(
      opacity: 1.0,
      duration: const Duration(milliseconds: 300),
      child: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.center,
            colors: [Colors.black54, Colors.transparent],
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(ZySpacing.base),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                IconButton(
                  icon: const Icon(Icons.fullscreen_exit_rounded,
                      color: Colors.white),
                  onPressed: onExitFullscreen,
                ),
                const SizedBox(width: ZySpacing.sm),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const SizedBox(height: 8),
                      Text(
                        cameraName,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
                const ZyBadge(
                  label: 'LIVE',
                  variant: ZyBadgeVariant.danger,
                  icon: Icons.fiber_manual_record,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
