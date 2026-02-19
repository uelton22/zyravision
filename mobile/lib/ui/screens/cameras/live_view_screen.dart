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
import '../../../domain/entities/timeline_data.dart';
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
  Timer? _seekDebounce;

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
  String? _currentHlsDate;
  int _currentHlsStartFrom = 0;
  bool _userSeekingTimeline = false; // Blocks _updateTimelinePosition during user scroll + playlist reload

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
        if (!_isLiveMode) {
          debugPrint('[HLS] buffering=$buffering pos=${_player.state.position.inSeconds}s dur=${_player.state.duration.inSeconds}s');
        }
      }
    });

    _player.stream.playing.listen((playing) {
      if (mounted) {
        setState(() => _isPlaying = playing);
        if (!_isLiveMode) {
          debugPrint('[HLS] playing=$playing pos=${_player.state.position.inSeconds}s');
          ref
              .read(timelineProvider(widget.cameraId).notifier)
              .setPlaying(playing);
        }
      }
    });

    _player.stream.position.listen((position) {
      if (!_isLiveMode && _player.state.playing) {
        // Log position every 5 seconds for debugging
        if (position.inSeconds % 5 == 0) {
          debugPrint('[HLS] position=${position.inSeconds}s duration=${_player.state.duration.inSeconds}s');
        }
      }
    });

    _player.stream.duration.listen((duration) {
      if (!_isLiveMode) {
        debugPrint('[HLS] duration changed: ${duration.inSeconds}s');
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
        debugPrint('[HLS] ERROR: $error');
        setState(() => _errorMessage = error);
      }
    });

    _player.stream.completed.listen((completed) {
      debugPrint('[HLS] completed=$completed');
      if (completed && !_isLiveMode) {
        ref.read(timelineProvider(widget.cameraId).notifier).setPlaying(false);
      }
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
    _seekDebounce?.cancel();
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
    _userSeekingTimeline = false;
    setState(() {
      _isLiveMode = true;
      _currentSegmentId = null;
      _currentHlsDate = null;
      _currentHlsStartFrom = 0;
      _errorMessage = null;
    });
    ref.read(timelineProvider(widget.cameraId).notifier).setPlaying(false);
    _initLiveStream();
  }

  // ─── HLS RECORDING PLAYBACK ─────────────────────────

  /// Opens a trimmed HLS playlist starting from the desired position.
  /// Instead of seeking within a loaded playlist (which MPV cannot do
  /// reliably for HLS), we request a new M3U8 that starts from the
  /// target segment. The player always starts at position 0.
  Future<void> _playHlsRecording(String date,
      {int startFromSeconds = 0}) async {
    if (_isLoadingSegment) return;
    setState(() {
      _isLoadingSegment = true;
      _isLiveMode = false;
      _errorMessage = null;
    });

    try {
      final token = await ref.read(secureStorageProvider).getToken();
      var hlsUrl =
          '${AppConfig.apiBaseUrl}${ApiPaths.cameraHlsPlaylist(widget.cameraId)}?date=$date';
      if (startFromSeconds > 0) {
        hlsUrl += '&start_from=$startFromSeconds';
      }

      debugPrint('[HLS] _playHlsRecording date=$date startFrom=${startFromSeconds}s url=$hlsUrl');

      if (!_surfaceReady) {
        await Future.delayed(const Duration(milliseconds: 800));
      }

      await _player.open(
        Media(hlsUrl, httpHeaders: {
          'Authorization': 'Bearer $token',
        }),
      );
      _currentHlsDate = date;
      _currentHlsStartFrom = startFromSeconds;

      debugPrint('[HLS] Playlist opened, calling play()');
      await _player.play();

      // Re-enable automatic timeline sync after playback starts
      _userSeekingTimeline = false;
    } catch (e) {
      debugPrint('[HLS] Error: $e');
      _userSeekingTimeline = false;
      if (mounted) {
        setState(() => _errorMessage = 'Erro ao reproduzir: $e');
      }
    } finally {
      if (mounted) setState(() => _isLoadingSegment = false);
    }
  }

  /// Calculate HLS playlist seek position from an absolute time.
  /// Maps real-world time -> cumulative playlist offset.
  Duration _calculateHlsSeekPosition(DateTime targetTime) {
    final timelineState = ref.read(timelineProvider(widget.cameraId));
    if (timelineState.data == null) {
      debugPrint('[HLS] _calculateHlsSeekPosition: no timeline data!');
      return Duration.zero;
    }

    final segments = timelineState.data!.segments;
    int cumulativeSeconds = 0;

    for (int i = 0; i < segments.length; i++) {
      final segment = segments[i];
      if (segment.startsAt == null) continue;
      final segEnd = segment.endsAt ??
          segment.startsAt!.add(
              Duration(seconds: segment.durationSeconds));

      if ((targetTime.isAfter(segment.startsAt!) || targetTime.isAtSameMomentAs(segment.startsAt!)) &&
          targetTime.isBefore(segEnd)) {
        final offsetInSegment =
            targetTime.difference(segment.startsAt!).inSeconds;
        final result = cumulativeSeconds + offsetInSegment;
        debugPrint('[HLS] _calculateHlsSeekPosition: target=$targetTime -> seg[$i] start=${segment.startsAt} offset=$offsetInSegment cumulative=$cumulativeSeconds -> result=${result}s');
        return Duration(seconds: result);
      }

      if (targetTime.isAfter(segEnd) || targetTime.isAtSameMomentAs(segEnd)) {
        cumulativeSeconds += segment.durationSeconds;
      }
    }

    debugPrint('[HLS] _calculateHlsSeekPosition: target=$targetTime NOT in any segment, returning ${cumulativeSeconds}s (total segments=${segments.length})');
    if (segments.isNotEmpty) {
      debugPrint('[HLS]   first seg starts=${segments.first.startsAt}, last seg starts=${segments.last.startsAt}');
    }
    return Duration(seconds: cumulativeSeconds);
  }

  void _onTimelineTimeSelected(DateTime time) {
    // Block automatic timeline updates while user is interacting
    _userSeekingTimeline = true;

    final notifier = ref.read(timelineProvider(widget.cameraId).notifier);
    notifier.setPlaybackTime(time);

    final date = '${time.year.toString().padLeft(4, '0')}-'
        '${time.month.toString().padLeft(2, '0')}-'
        '${time.day.toString().padLeft(2, '0')}';

    final seekPos = _calculateHlsSeekPosition(time);
    debugPrint('[HLS] _onTimelineTimeSelected time=$time seekPos=${seekPos.inSeconds}s');

    _seekDebounce?.cancel();
    _seekDebounce = Timer(const Duration(milliseconds: 500), () {
      _playHlsRecording(date, startFromSeconds: seekPos.inSeconds);
    });
  }

  void _updateTimelinePosition() {
    if (_isLiveMode || !_player.state.playing || _userSeekingTimeline) return;
    final timelineState = ref.read(timelineProvider(widget.cameraId));
    if (timelineState.data == null) return;

    final segments = timelineState.data!.segments;
    // Player pos is relative to the trimmed playlist (starting from _currentHlsStartFrom).
    // We need to add the offset back to map to the full cumulative timeline.
    final playerPos = _player.state.position.inSeconds + _currentHlsStartFrom;

    int cumulativeSeconds = 0;
    for (final segment in segments) {
      if (segment.startsAt == null) continue;
      final dur = segment.durationSeconds;

      if (playerPos >= cumulativeSeconds && playerPos < cumulativeSeconds + dur) {
        final offsetInSegment = playerPos - cumulativeSeconds;
        final currentTime = segment.startsAt!
            .add(Duration(seconds: offsetInSegment));
        ref
            .read(timelineProvider(widget.cameraId).notifier)
            .setPlaybackTime(currentTime);

        if (_currentSegmentId != segment.id) {
          _currentSegmentId = segment.id;
          ref
              .read(timelineProvider(widget.cameraId).notifier)
              .setActiveSegment(segment.id);
        }
        return;
      }

      cumulativeSeconds += dur;
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
                          if (_isLiveMode) {
                            _initLiveStream();
                          } else if (_currentHlsDate != null) {
                            _playHlsRecording(_currentHlsDate!,
                                startFromSeconds: _currentHlsStartFrom);
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

// ─── VERTICAL TIMELINE (CONDENSED) ────────────────────
//
// Shows ONLY time periods with recordings, not the full 24 hours.
// Gaps between recordings are shown as compact separators.
// Scrolling/dragging navigates video playback.

enum _BlockType { recording, gap }

class _LayoutBlock {
  final _BlockType type;
  final double startY;
  final double height;
  final TimelineRange? range;
  final DateTime? gapStart;
  final DateTime? gapEnd;

  const _LayoutBlock({
    required this.type,
    required this.startY,
    required this.height,
    this.range,
    this.gapStart,
    this.gapEnd,
  });

  double get endY => startY + height;
}

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
  static const double _pixelsPerMinute = 6.0;
  static const double _gapHeight = 52.0;
  static const double _minRangeHeight = 80.0;
  bool _isUserScrolling = false;
  bool _programmaticScroll = false;

  // Pre-calculated condensed layout
  List<_LayoutBlock> _layout = [];
  double _totalContentHeight = 0;

  DateTime get _firstRecordingTime {
    final ranges = widget.timelineState.ranges;
    if (ranges.isNotEmpty) return ranges.first.start;
    return DateTime.now();
  }

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    _buildLayout();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _scrollToTime(
          widget.currentPlaybackTime ?? _firstRecordingTime,
          animate: false);
    });
  }

  @override
  void didUpdateWidget(_VerticalTimeline old) {
    super.didUpdateWidget(old);

    if (old.timelineState.ranges != widget.timelineState.ranges ||
        old.timelineState.selectedDate != widget.timelineState.selectedDate) {
      _buildLayout();
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _scrollToTime(
            widget.currentPlaybackTime ?? _firstRecordingTime,
            animate: false);
      });
    }

    // Follow playback position when not user-scrolling
    // Must defer to avoid setState during build (scroll notifications → AppBar)
    if (!_isUserScrolling &&
        widget.currentPlaybackTime != null &&
        old.currentPlaybackTime != widget.currentPlaybackTime) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _scrollToTime(widget.currentPlaybackTime!, animate: false);
        }
      });
    }
  }

  /// Build condensed layout: only recording ranges + gap indicators.
  void _buildLayout() {
    final ranges = widget.timelineState.ranges;
    _layout = [];
    double y = 0;

    for (int i = 0; i < ranges.length; i++) {
      // Gap before this range (if not first)
      if (i > 0) {
        _layout.add(_LayoutBlock(
          type: _BlockType.gap,
          startY: y,
          height: _gapHeight,
          gapStart: ranges[i - 1].end,
          gapEnd: ranges[i].start,
        ));
        y += _gapHeight;
      }

      // Recording range block
      final range = ranges[i];
      final durationMinutes = range.duration.inSeconds / 60.0;
      final height = (durationMinutes * _pixelsPerMinute)
          .clamp(_minRangeHeight, double.infinity);

      _layout.add(_LayoutBlock(
        type: _BlockType.recording,
        startY: y,
        height: height,
        range: range,
      ));
      y += height;
    }

    _totalContentHeight = y;
  }

  void _onScroll() {
    if (_programmaticScroll) return;
    if (_isUserScrolling && _scrollController.hasClients) {
      final time = _offsetToTime(_scrollController.offset);
      if (time != null) {
        widget.onTimeSelected(time);
      }
    }
  }

  /// Convert scroll offset → real-world DateTime.
  /// Returns null only if layout is empty.
  DateTime? _offsetToTime(double offset) {
    for (final block in _layout) {
      if (offset < block.startY || offset > block.endY) continue;

      if (block.type == _BlockType.recording) {
        final range = block.range!;
        final fraction =
            ((offset - block.startY) / block.height).clamp(0.0, 1.0);
        final seconds = (range.duration.inSeconds * fraction).round();
        return range.start.add(Duration(seconds: seconds));
      } else {
        // In a gap — snap to nearest recording boundary
        final mid = block.startY + block.height / 2;
        return offset < mid ? block.gapStart : block.gapEnd;
      }
    }
    final ranges = widget.timelineState.ranges;
    if (ranges.isNotEmpty) return ranges.last.end;
    return null;
  }

  /// Convert real-world DateTime → scroll offset.
  double _timeToOffset(DateTime time) {
    for (final block in _layout) {
      if (block.type != _BlockType.recording) continue;
      final range = block.range!;
      if (!time.isBefore(range.start) && time.isBefore(range.end)) {
        final fraction =
            time.difference(range.start).inSeconds / range.duration.inSeconds;
        return block.startY + (block.height * fraction);
      }
    }
    return _totalContentHeight;
  }

  void _scrollToTime(DateTime time, {bool animate = true}) {
    if (!_scrollController.hasClients) return;
    final maxExtent = _scrollController.position.maxScrollExtent;
    final target = _timeToOffset(time).clamp(0.0, maxExtent);
    _programmaticScroll = true;
    if (animate) {
      _scrollController
          .animateTo(target,
              duration: const Duration(milliseconds: 200),
              curve: Curves.easeOut)
          .then((_) => _programmaticScroll = false);
    } else {
      _scrollController.jumpTo(target);
      _programmaticScroll = false;
    }
  }

  DateTime get _playheadTime {
    if (widget.currentPlaybackTime != null) return widget.currentPlaybackTime!;
    if (!_scrollController.hasClients) return DateTime.now();
    return _offsetToTime(_scrollController.offset) ?? DateTime.now();
  }

  @override
  void dispose() {
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (widget.timelineState.isLoading) {
      return Container(
        color: const Color(0xFF1A2530),
        child: const Center(
          child: CircularProgressIndicator(
              strokeWidth: 2, color: Colors.white38),
        ),
      );
    }

    // Empty state: no recordings
    if (_layout.isEmpty || widget.timelineState.ranges.isEmpty) {
      return Container(
        color: const Color(0xFF1A2530),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.videocam_off_outlined,
                  color: Colors.white.withValues(alpha: 0.3), size: 48),
              const SizedBox(height: 12),
              Text(
                'Sem gravacoes neste dia',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.4),
                  fontSize: 14,
                ),
              ),
              if (widget.timelineState.data != null)
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text(
                    '${widget.timelineState.data!.totalSegments} segmentos',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.25),
                      fontSize: 11,
                    ),
                  ),
                ),
            ],
          ),
        ),
      );
    }

    final viewportPadding = MediaQuery.of(context).size.height * 0.4;

    return Container(
      color: const Color(0xFF1A2530),
      child: Stack(
        children: [
          // Scrollable condensed timeline
          NotificationListener<ScrollNotification>(
            onNotification: (notification) {
              if (notification is ScrollStartNotification &&
                  notification.dragDetails != null) {
                _isUserScrolling = true;
              } else if (notification is ScrollEndNotification) {
                _isUserScrolling = false;
              }
              return false;
            },
            child: SingleChildScrollView(
              controller: _scrollController,
              physics: const ClampingScrollPhysics(),
              child: SizedBox(
                height: _totalContentHeight + viewportPadding,
                width: MediaQuery.of(context).size.width,
                child: CustomPaint(
                  size: Size(MediaQuery.of(context).size.width,
                      _totalContentHeight + viewportPadding),
                  painter: _CondensedTimelinePainter(
                    layout: _layout,
                    segments: widget.timelineState.data?.segments ?? [],
                  ),
                ),
              ),
            ),
          ),
          // Fixed playhead at top
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: _FixedPlayhead(
              time: _playheadTime,
              isLive: widget.isLiveMode,
            ),
          ),
        ],
      ),
    );
  }
}

/// Fixed playhead indicator — always visible at the top of the timeline.
class _FixedPlayhead extends StatelessWidget {
  final DateTime time;
  final bool isLive;

  const _FixedPlayhead({required this.time, required this.isLive});

  @override
  Widget build(BuildContext context) {
    final t = time.toLocal();
    final timeStr = '${t.hour.toString().padLeft(2, '0')}:'
        '${t.minute.toString().padLeft(2, '0')}:'
        '${t.second.toString().padLeft(2, '0')}';

    return IgnorePointer(
      child: SizedBox(
        height: 28,
        child: Row(
          children: [
            Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              margin: const EdgeInsets.only(left: 4),
              decoration: BoxDecoration(
                color: isLive ? ZyColors.danger : ZyColors.primary,
                borderRadius: BorderRadius.circular(4),
                boxShadow: [
                  BoxShadow(
                    color: (isLive ? ZyColors.danger : ZyColors.primary)
                        .withValues(alpha: 0.4),
                    blurRadius: 6,
                  ),
                ],
              ),
              child: Text(
                timeStr,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  fontFamily: 'monospace',
                ),
              ),
            ),
            Expanded(
              child: Container(
                height: 2,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      isLive ? ZyColors.danger : ZyColors.primary,
                      (isLive ? ZyColors.danger : ZyColors.primary)
                          .withValues(alpha: 0.1),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Custom painter for condensed timeline.
/// Only renders recording ranges and gap indicators — not full 24h.
class _CondensedTimelinePainter extends CustomPainter {
  final List<_LayoutBlock> layout;
  final List segments;

  _CondensedTimelinePainter({
    required this.layout,
    required this.segments,
  });

  @override
  void paint(Canvas canvas, Size size) {
    for (final block in layout) {
      if (block.type == _BlockType.recording) {
        _drawRecordingBlock(canvas, size, block);
      } else {
        _drawGapBlock(canvas, size, block);
      }
    }
  }

  void _drawRecordingBlock(
      Canvas canvas, Size size, _LayoutBlock block) {
    final range = block.range!;
    final startY = block.startY;
    final height = block.height;
    const barLeft = 70.0;
    final barWidth = size.width - barLeft - 8;

    // Recording bar background with gradient
    final barRect =
        Rect.fromLTWH(barLeft, startY + 2, barWidth, height - 4);
    final gradient = LinearGradient(
      begin: Alignment.centerLeft,
      end: Alignment.centerRight,
      colors: [
        ZyColors.primary.withValues(alpha: 0.30),
        ZyColors.primary.withValues(alpha: 0.10),
      ],
    );
    final barPaint = Paint()..shader = gradient.createShader(barRect);
    canvas.drawRRect(
        RRect.fromRectAndRadius(barRect, const Radius.circular(4)),
        barPaint);

    // Left accent bar
    final accentPaint = Paint()
      ..color = ZyColors.primary.withValues(alpha: 0.7)
      ..strokeWidth = 3
      ..strokeCap = StrokeCap.round;
    canvas.drawLine(Offset(barLeft, startY + 4),
        Offset(barLeft, startY + height - 4), accentPaint);

    // Start time label (bold)
    _drawTimeLabel(canvas, range.start, startY + 4, bold: true);

    // End time label
    if (height > 50) {
      _drawTimeLabel(canvas, range.end, startY + height - 16,
          bold: false);
    }

    // Interval marks inside the recording bar
    final durationMin = range.duration.inMinutes;
    final interval = durationMin > 120 ? 30 : 15;
    if (durationMin > 0) {
      final startMinute = range.start.hour * 60 + range.start.minute;
      final firstMark = ((startMinute / interval).ceil() * interval)
          .toInt();

      for (var m = firstMark;
          m < startMinute + durationMin;
          m += interval) {
        final markTime = DateTime(
            range.start.year,
            range.start.month,
            range.start.day,
            m ~/ 60,
            m % 60);
        if (markTime.isBefore(range.start) ||
            !markTime.isBefore(range.end)) {
          continue;
        }

        final fraction = markTime.difference(range.start).inSeconds /
            range.duration.inSeconds;
        final markY = startY + (height * fraction);

        // Skip if too close to start/end labels
        if (markY - startY < 22 || startY + height - markY < 22) {
          continue;
        }

        // Tick line
        final tickPaint = Paint()
          ..color = Colors.white.withValues(alpha: 0.08)
          ..strokeWidth = 0.5;
        canvas.drawLine(Offset(barLeft + 4, markY),
            Offset(size.width - 8, markY), tickPaint);

        // Label
        final label =
            '${markTime.hour.toString().padLeft(2, '0')}:${markTime.minute.toString().padLeft(2, '0')}';
        final tp = TextPainter(
          text: TextSpan(
            text: label,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.20),
              fontSize: 9,
              fontFamily: 'monospace',
            ),
          ),
          textDirection: TextDirection.ltr,
        );
        tp.layout();
        tp.paint(canvas, Offset(6, markY - 5));
      }
    }

    // Draw segment tick marks inside the bar (small dots at segment boundaries)
    for (final seg in segments) {
      if (seg.startsAt == null) continue;
      if (seg.startsAt!.isBefore(range.start) ||
          !seg.startsAt!.isBefore(range.end)) {
        continue;
      }

      final fraction = seg.startsAt!.difference(range.start).inSeconds /
          range.duration.inSeconds;
      final segY = startY + (height * fraction);

      // Small segment indicator dot
      canvas.drawCircle(
        Offset(barLeft + barWidth - 12, segY),
        2.5,
        Paint()..color = ZyColors.primary.withValues(alpha: 0.6),
      );
    }

    // Duration label inside bar
    final durText = _formatDuration(range.duration);
    final durTp = TextPainter(
      text: TextSpan(
        text: durText,
        style: TextStyle(
          color: Colors.white.withValues(alpha: 0.35),
          fontSize: 10,
          fontWeight: FontWeight.w500,
        ),
      ),
      textDirection: TextDirection.ltr,
    );
    durTp.layout();
    if (height > 60) {
      durTp.paint(
          canvas, Offset(barLeft + 10, startY + height / 2 - 6));
    }
  }

  void _drawTimeLabel(Canvas canvas, DateTime time, double y,
      {required bool bold}) {
    final label =
        '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
    final style = TextStyle(
      color:
          Colors.white.withValues(alpha: bold ? 0.55 : 0.30),
      fontSize: bold ? 11 : 9,
      fontWeight: bold ? FontWeight.w600 : FontWeight.w400,
      fontFamily: 'monospace',
    );
    final tp = TextPainter(
      text: TextSpan(text: label, style: style),
      textDirection: TextDirection.ltr,
    );
    tp.layout();
    tp.paint(canvas, Offset(4, y));
  }

  void _drawGapBlock(Canvas canvas, Size size, _LayoutBlock block) {
    final centerY = block.startY + block.height / 2;

    // Dashed line
    final dashPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.06)
      ..strokeWidth = 1;
    for (double x = 8; x < size.width - 8; x += 8) {
      canvas.drawLine(
          Offset(x, centerY), Offset(x + 4, centerY), dashPaint);
    }

    // Gap duration label
    if (block.gapStart != null && block.gapEnd != null) {
      final gap = block.gapEnd!.difference(block.gapStart!);
      final gapText = '${_formatDuration(gap)} sem gravacao';

      final tp = TextPainter(
        text: TextSpan(
          text: gapText,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.15),
            fontSize: 10,
          ),
        ),
        textDirection: TextDirection.ltr,
      );
      tp.layout();

      // Background pill for readability
      final bgRect = Rect.fromCenter(
        center: Offset(size.width / 2, centerY),
        width: tp.width + 16,
        height: 20,
      );
      canvas.drawRRect(
        RRect.fromRectAndRadius(bgRect, const Radius.circular(10)),
        Paint()..color = const Color(0xFF1A2530),
      );

      tp.paint(canvas,
          Offset((size.width - tp.width) / 2, centerY - 7));
    }
  }

  String _formatDuration(Duration d) {
    if (d.inHours > 0) {
      return '${d.inHours}h ${d.inMinutes % 60}min';
    }
    if (d.inMinutes > 0) {
      return '${d.inMinutes}min';
    }
    return '${d.inSeconds}s';
  }

  @override
  bool shouldRepaint(covariant _CondensedTimelinePainter old) {
    return old.layout != layout || old.segments != segments;
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
