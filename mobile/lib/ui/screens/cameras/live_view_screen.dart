import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:media_kit/media_kit.dart';
import 'package:media_kit_video/media_kit_video.dart';

import '../../../core/config/app_config.dart';
import '../../../core/config/constants.dart';
import '../../../core/storage/secure_storage_service.dart';
import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../core/utils/url_helper.dart';
import '../../../state/camera/camera_providers.dart';
import '../../../state/recording/recording_providers.dart';
import '../../../state/recording/timeline_state.dart';
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
  Timer? _positionTimer;

  bool _isFullscreen = false;
  bool _controlsVisible = true;
  bool _isBuffering = true;
  bool _isPlaying = false;
  int _videoWidth = 0;
  int _videoHeight = 0;
  String? _errorMessage;
  bool _streamInitialized = false;
  bool _surfaceReady = false;

  // Live vs Recording mode
  bool _isLiveMode = true;
  String? _currentSegmentId;
  bool _isLoadingSegment = false;

  @override
  void initState() {
    super.initState();
    _player = Player(
      configuration: const PlayerConfiguration(
        logLevel: MPVLogLevel.v,
      ),
    );
    _videoController = VideoController(
      _player,
      configuration: const VideoControllerConfiguration(
        enableHardwareAcceleration: false,
      ),
    );

    _player.stream.buffering.listen((buffering) {
      if (mounted) {
        setState(() => _isBuffering = buffering);
      }
    });

    _player.stream.playing.listen((playing) {
      if (mounted) {
        setState(() => _isPlaying = playing);
        if (!_isLiveMode) {
          ref
              .read(timelineProvider(widget.cameraId).notifier)
              .setPlaying(playing);
        }
      }
    });

    _player.stream.width.listen((width) {
      if (mounted && width != null) setState(() => _videoWidth = width);
    });

    _player.stream.height.listen((height) {
      if (mounted && height != null) setState(() => _videoHeight = height);
    });

    _player.stream.error.listen((error) {
      if (mounted && error.isNotEmpty) {
        setState(() => _errorMessage = error);
      }
    });

    _player.stream.completed.listen((completed) {
      if (completed && !_isLiveMode) _playNextSegment();
    });

    _positionTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      _updateTimelinePosition();
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_streamInitialized) {
      _streamInitialized = true;
      WidgetsBinding.instance.addPostFrameCallback((_) async {
        if (!mounted) return;
        await Future.delayed(const Duration(milliseconds: 800));
        if (mounted) {
          _surfaceReady = true;
          _initLiveStream();
        }
      });
    }
  }

  @override
  void dispose() {
    _positionTimer?.cancel();
    _player.dispose();
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
    super.dispose();
  }

  // ─── LIVE STREAM ────────────────────────────────────────

  Future<void> _initLiveStream() async {
    try {
      setState(() {
        _errorMessage = null;
        _isBuffering = true;
      });

      final useCase = ref.read(getCameraDetailUseCaseProvider);
      final camera = await useCase(widget.cameraId);

      String? hlsUrl;
      if (camera.stream?.urls?.hls != null) {
        hlsUrl = UrlHelper.hlsUrlFromPath(camera.stream!.urls!.hls);
      }
      if (hlsUrl == null && camera.stream?.mediamtxPath != null) {
        hlsUrl = UrlHelper.hlsUrl(camera.stream!.mediamtxPath);
      }

      if (hlsUrl != null && mounted) {
        try {
          final testDio = Dio();
          await testDio.get<String>(hlsUrl);
          testDio.close();
        } catch (_) {}

        await _player.open(Media(hlsUrl));
      } else if (mounted) {
        setState(() => _errorMessage =
            'Stream HLS nao disponivel.\nVerifique se a camera esta online.');
      }
    } catch (e) {
      if (mounted) {
        setState(() => _errorMessage = 'Erro ao carregar stream: $e');
      }
    }
  }

  // ─── SWITCH TO LIVE ───────────────────────────────────

  void _switchToLive() {
    if (_isLiveMode) return;
    setState(() {
      _isLiveMode = true;
      _currentSegmentId = null;
      _errorMessage = null;
    });
    ref.read(timelineProvider(widget.cameraId).notifier).setPlaying(false);
    _initLiveStream();
  }

  // ─── RECORDING PLAYBACK ──────────────────────────────

  Future<void> _playSegment(String segmentId,
      {int offsetSeconds = 0}) async {
    if (_isLoadingSegment) return;
    setState(() {
      _isLoadingSegment = true;
      _isLiveMode = false;
      _errorMessage = null;
    });

    try {
      final token = await ref.read(secureStorageProvider).getToken();
      final url =
          '${AppConfig.apiBaseUrl}${ApiPaths.segmentStream(segmentId)}';

      try {
        final testDio = Dio(BaseOptions(
          connectTimeout: const Duration(seconds: 10),
          receiveTimeout: const Duration(seconds: 10),
        ));
        await testDio.head(
          url,
          options: Options(headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'video/mp4',
          }),
        );
        testDio.close();
      } catch (e) {
        if (e is DioException && e.response != null) {
          final statusCode = e.response?.statusCode ?? 0;
          String errorMsg;
          if (statusCode == 401) {
            errorMsg = 'Sessao expirada. Faca login novamente.';
          } else if (statusCode == 404) {
            errorMsg = 'Gravacao nao encontrada no servidor.';
          } else {
            errorMsg = 'Erro do servidor ($statusCode).';
          }
          if (mounted) {
            setState(() {
              _errorMessage = errorMsg;
              _isLoadingSegment = false;
            });
          }
          return;
        }
      }

      if (!_surfaceReady) {
        await Future.delayed(const Duration(milliseconds: 800));
      }

      await _player.open(
        Media(url, httpHeaders: {
          'Authorization': 'Bearer $token',
          'Accept': 'video/mp4',
        }),
      );

      if (offsetSeconds > 0) {
        await _player.seek(Duration(seconds: offsetSeconds));
      }

      _currentSegmentId = segmentId;
      ref
          .read(timelineProvider(widget.cameraId).notifier)
          .setActiveSegment(segmentId);

      await _player.play();
    } catch (e) {
      if (mounted) {
        setState(() => _errorMessage = 'Erro ao reproduzir: $e');
      }
    } finally {
      if (mounted) setState(() => _isLoadingSegment = false);
    }
  }

  void _playNextSegment() {
    final timelineState = ref.read(timelineProvider(widget.cameraId));
    if (timelineState.data == null || _currentSegmentId == null) return;

    final segments = timelineState.data!.segments;
    final currentIndex =
        segments.indexWhere((s) => s.id == _currentSegmentId);

    if (currentIndex >= 0 && currentIndex < segments.length - 1) {
      _playSegment(segments[currentIndex + 1].id);
    } else {
      ref.read(timelineProvider(widget.cameraId).notifier).setPlaying(false);
    }
  }

  void _onTimelineTimeSelected(DateTime time) {
    final notifier = ref.read(timelineProvider(widget.cameraId).notifier);
    final info = notifier.findSegmentAtTime(time);
    if (info != null) {
      notifier.setPlaybackTime(time);
      _playSegment(info.segmentId, offsetSeconds: info.offsetSeconds);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Nenhuma gravacao neste horario'),
          duration: Duration(seconds: 2),
        ),
      );
    }
  }

  void _updateTimelinePosition() {
    if (_isLiveMode) return;
    final timelineState = ref.read(timelineProvider(widget.cameraId));
    if (timelineState.activeSegmentId == null || !_player.state.playing) return;

    final activeSegment = timelineState.data?.segments.firstWhere(
      (s) => s.id == timelineState.activeSegmentId,
      orElse: () => timelineState.data!.segments.first,
    );

    if (activeSegment?.startsAt != null) {
      final playerPos = _player.state.position;
      final currentTime = activeSegment!.startsAt!
          .add(Duration(seconds: playerPos.inSeconds));
      ref
          .read(timelineProvider(widget.cameraId).notifier)
          .setPlaybackTime(currentTime);
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
      SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
    }
  }

  // ─── BUILD ─────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final timelineState = ref.watch(timelineProvider(widget.cameraId));

    if (_isFullscreen) return _buildFullscreenPlayer();

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        titleSpacing: 0,
        title: Text(
          widget.cameraName,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.settings_outlined, size: 20),
            onPressed: () {},
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
          // Mode label + controls
          _ModeHeader(
            isLiveMode: _isLiveMode,
            timelineState: timelineState,
            onAlertTap: () {},
          ),
          // Vertical timeline
          Expanded(
            child: _VerticalTimeline(
              timelineState: timelineState,
              isLiveMode: _isLiveMode,
              onTimeSelected: _onTimelineTimeSelected,
              currentPlaybackTime: _isLiveMode
                  ? null
                  : timelineState.currentPlaybackTime,
            ),
          ),
          // Bottom bar
          _BottomBar(
            isLiveMode: _isLiveMode,
            timelineState: timelineState,
            onLiveTap: _switchToLive,
            onDateTap: () => _showDatePicker(context, timelineState),
            onSaveTap: () => _showSaveOptions(context),
          ),
        ],
      ),
    );
  }

  Widget _buildFullscreenPlayer() {
    return Scaffold(
      backgroundColor: Colors.black,
      body: GestureDetector(
        onTap: () => setState(() => _controlsVisible = !_controlsVisible),
        child: Stack(
          fit: StackFit.expand,
          children: [
            Video(
              controller: _videoController,
              controls: NoVideoControls,
              fill: Colors.black,
            ),
            if (_controlsVisible)
              _FullscreenOverlay(
                cameraName: widget.cameraName,
                isLiveMode: _isLiveMode,
                onExitFullscreen: _toggleFullscreen,
                onSwitchToLive: _switchToLive,
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildVideoPlayer() {
    return GestureDetector(
      onTap: () {},
      onDoubleTap: _toggleFullscreen,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Video(
            controller: _videoController,
            controls: NoVideoControls,
            fill: Colors.black,
          ),
          // Error overlay
          if (_errorMessage != null)
            Container(
              color: Colors.black87,
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.all(ZySpacing.base),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.error_outline,
                          color: ZyColors.danger, size: 36),
                      const SizedBox(height: ZySpacing.sm),
                      Text(
                        _errorMessage!,
                        style: const TextStyle(
                            color: Colors.white70, fontSize: 12),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: ZySpacing.sm),
                      OutlinedButton(
                        onPressed: () {
                          setState(() => _errorMessage = null);
                          _isLiveMode ? _initLiveStream() : null;
                          if (!_isLiveMode && _currentSegmentId != null) {
                            _playSegment(_currentSegmentId!);
                          }
                        },
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.white,
                          side: const BorderSide(color: Colors.white38),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 16, vertical: 6),
                        ),
                        child: const Text('Tentar novamente',
                            style: TextStyle(fontSize: 12)),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          // Buffering/loading overlay
          if ((_isBuffering || _isLoadingSegment) && _errorMessage == null)
            Container(
              color: Colors.black54,
              child: const Center(
                child: CircularProgressIndicator(
                  color: Colors.white,
                  strokeWidth: 2,
                ),
              ),
            ),
          // Badge top-left
          Positioned(
            top: 8,
            left: 8,
            child: _isLiveMode
                ? ZyBadge(
                    label: _isPlaying ? 'LIVE' : 'CONECTANDO',
                    variant: _isPlaying
                        ? ZyBadgeVariant.danger
                        : ZyBadgeVariant.warning,
                    icon: Icons.fiber_manual_record,
                  )
                : const ZyBadge(
                    label: 'REC',
                    variant: ZyBadgeVariant.danger,
                    size: ZyBadgeSize.sm,
                  ),
          ),
          // Resolution and controls top-right
          Positioned(
            top: 8,
            right: 8,
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (_videoWidth > 0 && _videoHeight > 0)
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: Colors.black54,
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      _isLiveMode
                          ? '${_videoWidth}x$_videoHeight'
                          : '1X',
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 10,
                        fontFamily: 'monospace',
                      ),
                    ),
                  ),
                const SizedBox(width: 4),
                GestureDetector(
                  onTap: () {},
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: Colors.black38,
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: const Icon(Icons.camera_alt_outlined,
                        color: Colors.white70, size: 18),
                  ),
                ),
                const SizedBox(width: 4),
                GestureDetector(
                  onTap: _toggleFullscreen,
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: Colors.black38,
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: const Icon(Icons.fullscreen,
                        color: Colors.white70, size: 18),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showDatePicker(
      BuildContext context, TimelineState timelineState) async {
    final result = await showDatePicker(
      context: context,
      initialDate: timelineState.selectedDate ?? DateTime.now(),
      firstDate: DateTime(2024),
      lastDate: DateTime.now(),
      locale: const Locale('pt', 'BR'),
    );
    if (result != null) {
      ref
          .read(timelineProvider(widget.cameraId).notifier)
          .selectDate(result);
    }
  }

  void _showSaveOptions(BuildContext context) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.all(ZySpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Theme.of(context)
                        .colorScheme
                        .outline
                        .withValues(alpha: 0.3),
                    borderRadius: ZySpacing.borderRadiusFull,
                  ),
                ),
              ),
              const SizedBox(height: ZySpacing.xl),
              Text(
                'Salvar',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: ZySpacing.base),
              ListTile(
                leading: const Icon(Icons.download),
                title: const Text('Download do segmento atual'),
                subtitle:
                    const Text('Baixar o segmento que esta reproduzindo'),
                enabled: _currentSegmentId != null,
                onTap: () {
                  Navigator.pop(context);
                  if (_currentSegmentId != null) {
                    final url =
                        '${AppConfig.apiBaseUrl}${ApiPaths.segmentDownload(_currentSegmentId!)}';
                    debugPrint('[Download] URL: $url');
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Download iniciado...')),
                    );
                  }
                },
              ),
              ListTile(
                leading: const Icon(Icons.content_cut),
                title: const Text('Criar clip'),
                subtitle:
                    const Text('Recortar uma parte da gravacao'),
                onTap: () {
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Funcao de clip em desenvolvimento'),
                    ),
                  );
                },
              ),
              SizedBox(
                height:
                    MediaQuery.of(context).padding.bottom + ZySpacing.base,
              ),
            ],
          ),
        );
      },
    );
  }
}

// ─── MODE HEADER (Ao vivo / Hoje + Alerta) ─────────────

class _ModeHeader extends StatelessWidget {
  final bool isLiveMode;
  final TimelineState timelineState;
  final VoidCallback onAlertTap;

  const _ModeHeader({
    required this.isLiveMode,
    required this.timelineState,
    required this.onAlertTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final date = timelineState.selectedDate ?? DateTime.now();
    final now = DateTime.now();
    final isToday = date.year == now.year &&
        date.month == now.month &&
        date.day == now.day;

    String label;
    if (isLiveMode) {
      label = 'Ao vivo';
    } else if (isToday) {
      label = 'Hoje';
    } else {
      label =
          '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
    }

    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: ZySpacing.base,
        vertical: ZySpacing.sm,
      ),
      color: theme.colorScheme.surface,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: theme.textTheme.bodyMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          GestureDetector(
            onTap: onAlertTap,
            child: Text(
              'Alerta',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: ZyColors.primary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ─── VERTICAL TIMELINE ─────────────────────────────────

class _VerticalTimeline extends StatefulWidget {
  final TimelineState timelineState;
  final bool isLiveMode;
  final ValueChanged<DateTime> onTimeSelected;
  final DateTime? currentPlaybackTime;

  const _VerticalTimeline({
    required this.timelineState,
    required this.isLiveMode,
    required this.onTimeSelected,
    this.currentPlaybackTime,
  });

  @override
  State<_VerticalTimeline> createState() => _VerticalTimelineState();
}

class _VerticalTimelineState extends State<_VerticalTimeline> {
  final ScrollController _scrollController = ScrollController();
  // 5 pixels per minute = 300px per hour, good spacing for thumbnails
  static const double _pixelsPerMinute = 5.0;
  static const double _totalHeight = _pixelsPerMinute * 60 * 24;
  static const double _timeColumnWidth = 60.0;
  static const double _thumbWidth = 140.0;
  static const double _thumbHeight = 80.0;
  // Min spacing between thumbnails to prevent overlap
  static const double _minThumbSpacing = 90.0;

  DateTime get _dayStart {
    final date = widget.timelineState.selectedDate ?? DateTime.now();
    return DateTime(date.year, date.month, date.day);
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance
        .addPostFrameCallback((_) => _scrollToCurrentTime());
  }

  @override
  void didUpdateWidget(_VerticalTimeline old) {
    super.didUpdateWidget(old);
    if (old.timelineState.selectedDate != widget.timelineState.selectedDate) {
      WidgetsBinding.instance
          .addPostFrameCallback((_) => _scrollToCurrentTime());
    }
  }

  void _scrollToCurrentTime() {
    final time = widget.currentPlaybackTime ?? DateTime.now();
    final offset = _timeToY(time);
    final viewportHeight = _scrollController.hasClients
        ? _scrollController.position.viewportDimension
        : MediaQuery.of(context).size.height * 0.4;
    final target =
        (offset - viewportHeight / 2).clamp(0.0, _totalHeight);

    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        target,
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeOut,
      );
    }
  }

  double _timeToY(DateTime time) {
    final diff = time.difference(_dayStart);
    return (diff.inMinutes * _pixelsPerMinute).clamp(0.0, _totalHeight);
  }

  DateTime _yToTime(double y) {
    final minutes = y / _pixelsPerMinute;
    return _dayStart.add(Duration(minutes: minutes.round()));
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ranges = widget.timelineState.ranges;
    final segments = widget.timelineState.data?.segments ?? [];

    if (widget.timelineState.isLoading) {
      return Container(
        color: const Color(0xFF2C3E50),
        child: const Center(
          child: CircularProgressIndicator(strokeWidth: 2),
        ),
      );
    }

    return Container(
      color: const Color(0xFF2C3E50),
      child: GestureDetector(
        onTapUp: (details) {
          final renderBox = context.findRenderObject() as RenderBox;
          final localPos =
              renderBox.globalToLocal(details.globalPosition);
          final scrollOffset = _scrollController.offset;
          final y = scrollOffset + localPos.dy;
          final time = _yToTime(y);
          widget.onTimeSelected(time);
        },
        child: SingleChildScrollView(
          controller: _scrollController,
          child: SizedBox(
            height: _totalHeight,
            child: CustomPaint(
              size: Size(
                  MediaQuery.of(context).size.width, _totalHeight),
              painter: _VerticalTimelinePainter(
                dayStart: _dayStart,
                ranges: ranges,
                pixelsPerMinute: _pixelsPerMinute,
                timeColumnWidth: _timeColumnWidth,
              ),
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                  // Segment thumbnails
                  ..._buildThumbnails(segments),
                  // Playback time indicator (recording mode)
                  if (!widget.isLiveMode &&
                      widget.currentPlaybackTime != null)
                    _buildTimeIndicator(
                      widget.currentPlaybackTime!,
                      ZyColors.primary,
                    ),
                  // Now indicator (live mode)
                  if (widget.isLiveMode)
                    _buildTimeIndicator(DateTime.now(), ZyColors.primary),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  /// Build thumbnails with proper spacing to prevent overlap.
  /// Groups close segments and ensures minimum vertical distance between cards.
  List<Widget> _buildThumbnails(List segments) {
    final widgets = <Widget>[];

    if (segments.isEmpty) return widgets;

    // Filter segments with valid start times and sort
    final validSegments = segments
        .where((s) => s.startsAt != null)
        .toList()
      ..sort((a, b) => a.startsAt!.compareTo(b.startsAt!));

    if (validSegments.isEmpty) return widgets;

    // Place thumbnails ensuring no overlap
    double lastPlacedY = -_minThumbSpacing * 2;

    for (final segment in validSegments) {
      final idealY = _timeToY(segment.startsAt!);

      // Skip if too close to last placed thumbnail
      if ((idealY - lastPlacedY) < _minThumbSpacing) continue;

      final isActive =
          widget.timelineState.activeSegmentId == segment.id;

      final thumbnailUrl = segment.thumbnailUrl;
      final hasThumbnail =
          thumbnailUrl != null && thumbnailUrl.isNotEmpty;

      widgets.add(
        Positioned(
          top: idealY - _thumbHeight / 2,
          right: 12,
          child: GestureDetector(
            onTap: () => widget.onTimeSelected(segment.startsAt!),
            child: Container(
              width: _thumbWidth,
              height: _thumbHeight,
              decoration: BoxDecoration(
                color: const Color(0xFF1A252F),
                borderRadius: BorderRadius.circular(6),
                border: Border.all(
                  color: isActive ? ZyColors.primary : Colors.white24,
                  width: isActive ? 2.0 : 0.5,
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.3),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              clipBehavior: Clip.antiAlias,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  // Thumbnail image or placeholder
                  if (hasThumbnail)
                    Image.network(
                      _buildThumbnailUrl(thumbnailUrl),
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) =>
                          _thumbnailPlaceholder(),
                    )
                  else
                    _thumbnailPlaceholder(),
                  // Time overlay at bottom-left
                  Positioned(
                    bottom: 3,
                    left: 4,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 5, vertical: 2),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.7),
                        borderRadius: BorderRadius.circular(3),
                      ),
                      child: Text(
                        _formatTimeShort(segment.startsAt!),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.w500,
                          fontFamily: 'monospace',
                        ),
                      ),
                    ),
                  ),
                  // Duration overlay at bottom-right
                  if (segment.durationSeconds > 0)
                    Positioned(
                      bottom: 3,
                      right: 4,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 4, vertical: 2),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.7),
                          borderRadius: BorderRadius.circular(3),
                        ),
                        child: Text(
                          _formatDuration(segment.durationSeconds),
                          style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 9,
                            fontFamily: 'monospace',
                          ),
                        ),
                      ),
                    ),
                  // Active indicator
                  if (isActive)
                    Positioned(
                      top: 3,
                      right: 4,
                      child: Container(
                        width: 8,
                        height: 8,
                        decoration: const BoxDecoration(
                          color: ZyColors.danger,
                          shape: BoxShape.circle,
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ),
      );

      // Draw a line from timeline to thumbnail
      widgets.add(
        Positioned(
          top: idealY - 0.5,
          left: _timeColumnWidth,
          right: 12 + _thumbWidth,
          child: Container(
            height: 1,
            color: isActive
                ? ZyColors.primary.withValues(alpha: 0.6)
                : Colors.white.withValues(alpha: 0.15),
          ),
        ),
      );

      lastPlacedY = idealY;
    }

    return widgets;
  }

  Widget _thumbnailPlaceholder() {
    return Container(
      color: const Color(0xFF1A252F),
      child: const Center(
        child: Icon(
          Icons.videocam_outlined,
          color: Colors.white24,
          size: 28,
        ),
      ),
    );
  }

  String _buildThumbnailUrl(String path) {
    if (path.startsWith('http')) return path;
    return '${AppConfig.apiBaseUrl.replaceAll('/api/v1', '')}$path';
  }

  /// Time indicator with label showing the current time
  Widget _buildTimeIndicator(DateTime time, Color color) {
    final y = _timeToY(time);

    return Positioned(
      top: y - 10,
      left: 0,
      right: 0,
      child: SizedBox(
        height: 20,
        child: Row(
          children: [
            // Time label
            Container(
              width: _timeColumnWidth - 2,
              alignment: Alignment.centerRight,
              padding: const EdgeInsets.only(right: 4),
              child: Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: 4, vertical: 1),
                decoration: BoxDecoration(
                  color: color,
                  borderRadius: BorderRadius.circular(3),
                ),
                child: Text(
                  _formatTimeShort(time),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 9,
                    fontWeight: FontWeight.w700,
                    fontFamily: 'monospace',
                  ),
                ),
              ),
            ),
            // Line with dot
            Expanded(
              child: Stack(
                alignment: Alignment.centerLeft,
                children: [
                  Container(
                    height: 2,
                    color: color,
                  ),
                  Container(
                    width: 10,
                    height: 10,
                    decoration: BoxDecoration(
                      color: color,
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 1.5),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatTimeShort(DateTime time) {
    final t = time.toLocal();
    return '${t.hour.toString().padLeft(2, '0')}:'
        '${t.minute.toString().padLeft(2, '0')}:'
        '${t.second.toString().padLeft(2, '0')}';
  }

  String _formatDuration(int seconds) {
    final m = seconds ~/ 60;
    final s = seconds % 60;
    if (m > 0) return '${m}m${s > 0 ? ' ${s}s' : ''}';
    return '${s}s';
  }
}

class _VerticalTimelinePainter extends CustomPainter {
  final DateTime dayStart;
  final List ranges;
  final double pixelsPerMinute;
  final double timeColumnWidth;

  _VerticalTimelinePainter({
    required this.dayStart,
    required this.ranges,
    required this.pixelsPerMinute,
    required this.timeColumnWidth,
  });

  @override
  void paint(Canvas canvas, Size size) {
    _drawGrid(canvas, size);
    _drawRanges(canvas, size);
  }

  void _drawGrid(Canvas canvas, Size size) {
    final gridPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.06)
      ..strokeWidth = 0.5;

    final subGridPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.03)
      ..strokeWidth = 0.5;

    final textStyle = TextStyle(
      color: Colors.white.withValues(alpha: 0.35),
      fontSize: 10,
      fontFamily: 'monospace',
    );

    for (var hour = 0; hour <= 24; hour++) {
      final y = hour * 60 * pixelsPerMinute;

      // Hour line
      canvas.drawLine(
        Offset(timeColumnWidth, y),
        Offset(size.width, y),
        gridPaint,
      );

      // Hour label
      if (hour < 24) {
        final label = '${hour.toString().padLeft(2, '0')}h';
        final tp = TextPainter(
          text: TextSpan(text: label, style: textStyle),
          textDirection: TextDirection.ltr,
        );
        tp.layout();
        tp.paint(canvas, Offset(6, y + 4));

        // 30-minute sub-grid
        final halfY = y + 30 * pixelsPerMinute;
        canvas.drawLine(
          Offset(timeColumnWidth, halfY),
          Offset(size.width, halfY),
          subGridPaint,
        );
      }
    }

    // Vertical separator line
    canvas.drawLine(
      Offset(timeColumnWidth, 0),
      Offset(timeColumnWidth, size.height),
      gridPaint,
    );
  }

  void _drawRanges(Canvas canvas, Size size) {
    final rangePaint = Paint()
      ..color = ZyColors.primary.withValues(alpha: 0.2);
    final rangeBorderPaint = Paint()
      ..color = ZyColors.primary.withValues(alpha: 0.4)
      ..strokeWidth = 1
      ..style = PaintingStyle.stroke;

    for (final range in ranges) {
      final startY = _timeToY(range.start);
      final endY = _timeToY(range.end);

      final rect = Rect.fromLTRB(
          timeColumnWidth + 1, startY, size.width, endY);
      canvas.drawRect(rect, rangePaint);
      canvas.drawLine(
        Offset(timeColumnWidth + 1, startY),
        Offset(timeColumnWidth + 1, endY),
        rangeBorderPaint..strokeWidth = 3,
      );
    }
  }

  double _timeToY(DateTime time) {
    final diff = time.difference(dayStart);
    return diff.inMinutes * pixelsPerMinute;
  }

  @override
  bool shouldRepaint(covariant _VerticalTimelinePainter old) {
    return old.ranges != ranges;
  }
}

// ─── BOTTOM BAR ─────────────────────────────────────────

class _BottomBar extends StatelessWidget {
  final bool isLiveMode;
  final TimelineState timelineState;
  final VoidCallback onLiveTap;
  final VoidCallback onDateTap;
  final VoidCallback onSaveTap;

  const _BottomBar({
    required this.isLiveMode,
    required this.timelineState,
    required this.onLiveTap,
    required this.onDateTap,
    required this.onSaveTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bottomPadding = MediaQuery.of(context).padding.bottom;

    return Container(
      padding: EdgeInsets.only(
        left: ZySpacing.xl,
        right: ZySpacing.xl,
        top: ZySpacing.md,
        bottom: bottomPadding + ZySpacing.sm,
      ),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        border: Border(
          top: BorderSide(
            color: theme.colorScheme.outline.withValues(alpha: 0.1),
          ),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          // Date / days
          _BottomBarItem(
            icon: Icons.calendar_today_outlined,
            label: _getDateLabel(),
            onTap: onDateTap,
            isActive: false,
          ),
          // Ao Vivo
          _BottomBarItem(
            icon: Icons.expand_less,
            label: 'Ao vivo',
            onTap: onLiveTap,
            isActive: !isLiveMode,
          ),
          // Salvar
          _BottomBarItem(
            icon: Icons.download_outlined,
            label: 'Salvar',
            onTap: onSaveTap,
            isActive: false,
          ),
        ],
      ),
    );
  }

  String _getDateLabel() {
    final dates = timelineState.availableDates;
    if (dates.isEmpty) return 'Datas';
    return '${dates.length} dia${dates.length != 1 ? 's' : ''}';
  }
}

class _BottomBarItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool isActive;

  const _BottomBarItem({
    required this.icon,
    required this.label,
    required this.onTap,
    required this.isActive,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final color =
        isActive ? ZyColors.primary : theme.colorScheme.onSurface;

    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 2),
          Text(
            label,
            style: theme.textTheme.labelSmall?.copyWith(
              color: color,
              fontWeight:
                  isActive ? FontWeight.w600 : FontWeight.w400,
              fontSize: 11,
            ),
          ),
        ],
      ),
    );
  }
}

// ─── FULLSCREEN OVERLAY ──────────────────────────────────

class _FullscreenOverlay extends StatelessWidget {
  final String cameraName;
  final bool isLiveMode;
  final VoidCallback onExitFullscreen;
  final VoidCallback onSwitchToLive;

  const _FullscreenOverlay({
    required this.cameraName,
    required this.isLiveMode,
    required this.onExitFullscreen,
    required this.onSwitchToLive,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
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
              ZyBadge(
                label: isLiveMode ? 'LIVE' : 'REC',
                variant: ZyBadgeVariant.danger,
                icon: Icons.fiber_manual_record,
              ),
              if (!isLiveMode) ...[
                const SizedBox(width: ZySpacing.sm),
                GestureDetector(
                  onTap: onSwitchToLive,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: ZyColors.primary,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: const Text(
                      'Ao Vivo',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
