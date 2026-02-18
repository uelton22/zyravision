import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:media_kit/media_kit.dart';
import 'package:media_kit_video/media_kit_video.dart';

import '../../../core/config/app_config.dart';
import '../../../core/config/constants.dart';
import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../core/storage/secure_storage_service.dart';
import '../../../state/recording/recording_providers.dart';
import '../../../state/recording/timeline_state.dart';
import '../../widgets/feedback/zy_badge.dart';
import '../../widgets/recordings/zy_date_picker_bar.dart';
import '../../widgets/recordings/zy_playback_controls.dart';
import '../../widgets/recordings/zy_timeline_bar.dart';

class RecordingPlaybackScreen extends ConsumerStatefulWidget {
  final String cameraId;
  final String? recordingId;
  final String? cameraName;

  const RecordingPlaybackScreen({
    super.key,
    required this.cameraId,
    this.recordingId,
    this.cameraName,
  });

  @override
  ConsumerState<RecordingPlaybackScreen> createState() =>
      _RecordingPlaybackScreenState();
}

class _RecordingPlaybackScreenState
    extends ConsumerState<RecordingPlaybackScreen> {
  late final Player _player;
  late final VideoController _videoController;
  Timer? _positionTimer;
  bool _isFullscreen = false;
  String? _currentSegmentId;
  bool _isLoadingSegment = false;
  bool _isBuffering = false;
  String? _errorMessage;
  bool _surfaceReady = false;

  @override
  void initState() {
    super.initState();

    // Mesma configuracao do LiveView: log verbose + SW decoding
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
        enableHardwareAcceleration: false,
      ),
    );

    _player.stream.buffering.listen((buffering) {
      if (mounted) {
        setState(() => _isBuffering = buffering);
        debugPrint('[Playback] Buffering: $buffering');
      }
    });

    _player.stream.playing.listen((playing) {
      if (mounted) {
        debugPrint('[Playback] Playing: $playing');
      }
      final notifier = ref.read(timelineProvider(widget.cameraId).notifier);
      notifier.setPlaying(playing);
    });

    _player.stream.completed.listen((completed) {
      debugPrint('[Playback] Completed: $completed');
      if (completed) {
        _playNextSegment();
      }
    });

    _player.stream.error.listen((error) {
      debugPrint('[Playback] Player error: $error');
      if (mounted && error.isNotEmpty) {
        setState(() => _errorMessage = error);
      }
    });

    _player.stream.log.listen((log) {
      debugPrint('[Playback][mpv] ${log.prefix}: ${log.text}');
    });

    _player.stream.width.listen((width) {
      if (width != null) {
        debugPrint('[Playback] Video width: $width');
      }
    });

    _player.stream.height.listen((height) {
      if (height != null) {
        debugPrint('[Playback] Video height: $height');
      }
    });

    // Update timeline position based on player position
    _positionTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      _updateTimelinePosition();
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_surfaceReady) {
      _surfaceReady = true;
      // Aguarda a surface nativa ser inicializada (mesmo pattern do LiveView)
      WidgetsBinding.instance.addPostFrameCallback((_) async {
        if (!mounted) return;
        await Future.delayed(const Duration(milliseconds: 800));
        if (mounted) {
          debugPrint('[Playback] Surface ready, player initialized');
        }
      });
    }
  }

  @override
  void dispose() {
    _positionTimer?.cancel();
    _player.dispose();
    if (_isFullscreen) {
      SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    }
    super.dispose();
  }

  void _updateTimelinePosition() {
    final timelineState = ref.read(timelineProvider(widget.cameraId));
    if (timelineState.activeSegmentId == null || !_player.state.playing) return;

    final activeSegment = timelineState.data?.segments.firstWhere(
      (s) => s.id == timelineState.activeSegmentId,
      orElse: () => timelineState.data!.segments.first,
    );

    if (activeSegment?.startsAt != null) {
      final playerPos = _player.state.position;
      final currentTime =
          activeSegment!.startsAt!.add(Duration(seconds: playerPos.inSeconds));
      ref.read(timelineProvider(widget.cameraId).notifier).setPlaybackTime(currentTime);
    }
  }

  Future<void> _playSegment(String segmentId, {int offsetSeconds = 0}) async {
    if (_isLoadingSegment) return;
    setState(() {
      _isLoadingSegment = true;
      _errorMessage = null;
    });

    try {
      final token = await ref.read(secureStorageProvider).getToken();
      final url =
          '${AppConfig.apiBaseUrl}${ApiPaths.segmentStream(segmentId)}';

      debugPrint('[Playback] ─── Playing segment ───');
      debugPrint('[Playback] Segment ID: $segmentId');
      debugPrint('[Playback] Offset: $offsetSeconds s');
      debugPrint('[Playback] URL: $url');
      debugPrint('[Playback] Token: ${token != null ? '${token.substring(0, 10)}...' : 'NULL'}');

      // Pre-flight check: verifica se o endpoint retorna 200 (HEAD request)
      try {
        final testDio = Dio(BaseOptions(
          connectTimeout: const Duration(seconds: 10),
          receiveTimeout: const Duration(seconds: 10),
        ));
        final headResponse = await testDio.head(
          url,
          options: Options(headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'video/mp4',
          }),
        );
        debugPrint('[Playback] Pre-flight: status=${headResponse.statusCode}, '
            'content-type=${headResponse.headers['content-type']}, '
            'content-length=${headResponse.headers['content-length']}');
        testDio.close();
      } catch (e) {
        debugPrint('[Playback] Pre-flight FAILED: $e');
        if (e is DioException && e.response != null) {
          debugPrint('[Playback] Pre-flight response: '
              'status=${e.response?.statusCode}, '
              'data=${e.response?.data}');
          final statusCode = e.response?.statusCode ?? 0;
          String errorMsg;
          if (statusCode == 401) {
            errorMsg = 'Sessao expirada. Faca login novamente.';
          } else if (statusCode == 403) {
            errorMsg = 'Sem permissao para acessar esta gravacao.';
          } else if (statusCode == 404) {
            errorMsg = 'Arquivo de gravacao nao encontrado no servidor.';
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
        // Network error - continue trying anyway
        debugPrint('[Playback] Pre-flight network error, trying player anyway');
      }

      // Aguarda a surface estar pronta
      if (!_surfaceReady) {
        debugPrint('[Playback] Waiting for surface...');
        await Future.delayed(const Duration(milliseconds: 800));
      }

      debugPrint('[Playback] Opening media in player...');
      await _player.open(
        Media(url, httpHeaders: {
          'Authorization': 'Bearer $token',
          'Accept': 'video/mp4',
        }),
      );
      debugPrint('[Playback] Player.open() completed');

      if (offsetSeconds > 0) {
        debugPrint('[Playback] Seeking to $offsetSeconds s');
        await _player.seek(Duration(seconds: offsetSeconds));
      }

      _currentSegmentId = segmentId;
      ref
          .read(timelineProvider(widget.cameraId).notifier)
          .setActiveSegment(segmentId);

      debugPrint('[Playback] Calling player.play()');
      await _player.play();
      debugPrint('[Playback] play() completed');
    } catch (e, stack) {
      debugPrint('[Playback] ERROR playing segment: $e');
      debugPrint('[Playback] Stack: $stack');
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
    final currentIndex = segments.indexWhere((s) => s.id == _currentSegmentId);

    if (currentIndex >= 0 && currentIndex < segments.length - 1) {
      final nextSegment = segments[currentIndex + 1];
      debugPrint('[Playback] Auto-playing next segment: ${nextSegment.id}');
      _playSegment(nextSegment.id);
    } else {
      debugPrint('[Playback] No more segments');
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

  void _skipSeconds(int seconds) {
    final current = _player.state.position;
    final duration = _player.state.duration;
    final newPos = current + Duration(seconds: seconds);

    if (newPos.isNegative) {
      // Skip to previous segment
      _player.seek(Duration.zero);
    } else if (newPos > duration) {
      _playNextSegment();
    } else {
      _player.seek(newPos);
    }
  }

  void _toggleFullscreen() {
    setState(() => _isFullscreen = !_isFullscreen);
    if (_isFullscreen) {
      SystemChrome.setPreferredOrientations([
        DeviceOrientation.landscapeLeft,
        DeviceOrientation.landscapeRight,
      ]);
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
    } else {
      SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    }
  }

  @override
  Widget build(BuildContext context) {
    final timelineState = ref.watch(timelineProvider(widget.cameraId));
    final theme = Theme.of(context);

    if (_isFullscreen) {
      return _buildFullscreenPlayer(theme);
    }

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: Text(widget.cameraName ?? 'Gravacoes'),
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        elevation: 0,
        actions: [
          if (timelineState.hasData)
            IconButton(
              onPressed: () => _showDownloadOptions(context),
              icon: const Icon(Icons.download_outlined),
              tooltip: 'Download',
            ),
        ],
      ),
      body: Column(
        children: [
          // Video player
          _buildVideoPlayer(theme),
          // Controls area (scrollable)
          Expanded(
            child: Container(
              color: theme.colorScheme.surface,
              child: Column(
                children: [
                  // Date picker bar
                  ZyDatePickerBar(
                    selectedDate:
                        timelineState.selectedDate ?? DateTime.now(),
                    availableDates: timelineState.availableDates,
                    onDateSelected: (date) {
                      ref
                          .read(timelineProvider(widget.cameraId).notifier)
                          .selectDate(date);
                    },
                  ),
                  // Timeline bar
                  if (timelineState.hasData)
                    ZyTimelineBar(
                      selectedDate:
                          timelineState.selectedDate ?? DateTime.now(),
                      ranges: timelineState.ranges,
                      currentTime: timelineState.currentPlaybackTime,
                      onTimeSelected: _onTimelineTimeSelected,
                    )
                  else if (timelineState.isLoading)
                    const Padding(
                      padding: EdgeInsets.all(ZySpacing.xl),
                      child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                    )
                  else
                    _buildNoRecordingsMessage(theme),
                  // Info section
                  if (timelineState.hasData) _buildInfoSection(theme, timelineState),
                  const Spacer(),
                  // Playback controls
                  ZyPlaybackControls(
                    isPlaying: timelineState.isPlaying,
                    playbackSpeed: timelineState.playbackSpeed,
                    onPlayPause: () {
                      if (_player.state.playing) {
                        _player.pause();
                      } else if (_currentSegmentId != null) {
                        _player.play();
                      } else if (timelineState.hasData &&
                          timelineState.data!.segments.isNotEmpty) {
                        _playSegment(timelineState.data!.segments.first.id);
                      }
                    },
                    onSkipBackward: () => _skipSeconds(-10),
                    onSkipForward: () => _skipSeconds(10),
                    onSpeedChanged: (speed) {
                      ref
                          .read(timelineProvider(widget.cameraId).notifier)
                          .setPlaybackSpeed(speed);
                      _player.setRate(speed);
                    },
                    onFullscreen: _toggleFullscreen,
                  ),
                  SizedBox(height: MediaQuery.of(context).padding.bottom),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildVideoPlayer(ThemeData theme) {
    return AspectRatio(
      aspectRatio: 16 / 9,
      child: Stack(
        children: [
          // Video
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
                  padding: const EdgeInsets.all(ZySpacing.xl),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.error_outline,
                        size: 48,
                        color: ZyColors.danger,
                      ),
                      const SizedBox(height: ZySpacing.sm),
                      Text(
                        _errorMessage!,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: Colors.white.withValues(alpha: 0.9),
                        ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: ZySpacing.base),
                      TextButton.icon(
                        onPressed: () {
                          setState(() => _errorMessage = null);
                          if (_currentSegmentId != null) {
                            _playSegment(_currentSegmentId!);
                          }
                        },
                        icon: const Icon(Icons.refresh, color: Colors.white),
                        label: const Text(
                          'Tentar novamente',
                          style: TextStyle(color: Colors.white),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          // Loading/Buffering overlay
          if ((_isLoadingSegment || _isBuffering) && _errorMessage == null)
            Container(
              color: Colors.black54,
              child: const Center(
                child: CircularProgressIndicator(
                  color: Colors.white,
                  strokeWidth: 2,
                ),
              ),
            ),
          // No segment selected overlay
          if (_currentSegmentId == null &&
              !_isLoadingSegment &&
              _errorMessage == null)
            Container(
              color: Colors.black87,
              child: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.touch_app_outlined,
                      size: 48,
                      color: Colors.white.withValues(alpha: 0.6),
                    ),
                    const SizedBox(height: ZySpacing.sm),
                    Text(
                      'Toque na timeline ou pressione play',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: Colors.white.withValues(alpha: 0.7),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          // REC badge when playing
          if (_currentSegmentId != null && _errorMessage == null)
            const Positioned(
              top: 8,
              left: 8,
              child: ZyBadge(
                label: 'REC',
                variant: ZyBadgeVariant.danger,
                size: ZyBadgeSize.sm,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildFullscreenPlayer(ThemeData theme) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: GestureDetector(
        onTap: _toggleFullscreen,
        child: Video(
          controller: _videoController,
          controls: NoVideoControls,
          fill: Colors.black,
        ),
      ),
    );
  }

  Widget _buildNoRecordingsMessage(ThemeData theme) {
    return Padding(
      padding: const EdgeInsets.all(ZySpacing.xxl),
      child: Column(
        children: [
          Icon(
            Icons.videocam_off_outlined,
            size: 48,
            color: theme.colorScheme.outline,
          ),
          const SizedBox(height: ZySpacing.sm),
          Text(
            'Nenhuma gravacao nesta data',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.outline,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoSection(ThemeData theme, TimelineState timelineState) {
    final data = timelineState.data!;
    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: ZySpacing.base,
        vertical: ZySpacing.sm,
      ),
      child: Row(
        children: [
          _InfoChip(
            icon: Icons.videocam_outlined,
            label: '${data.totalSegments} segmentos',
            theme: theme,
          ),
          const SizedBox(width: ZySpacing.md),
          _InfoChip(
            icon: Icons.schedule_outlined,
            label: _formatTotalDuration(data.totalDurationSeconds),
            theme: theme,
          ),
          const SizedBox(width: ZySpacing.md),
          _InfoChip(
            icon: Icons.pie_chart_outline,
            label: '${data.coveragePercent.toStringAsFixed(1)}% cobertura',
            theme: theme,
          ),
        ],
      ),
    );
  }

  String _formatTotalDuration(int seconds) {
    final h = seconds ~/ 3600;
    final m = (seconds % 3600) ~/ 60;
    if (h > 0) return '${h}h ${m}m';
    return '${m}m';
  }

  void _showDownloadOptions(BuildContext context) {
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
                'Opcoes',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: ZySpacing.base),
              ListTile(
                leading: const Icon(Icons.download),
                title: const Text('Download do segmento atual'),
                subtitle: const Text('Baixar o segmento que esta reproduzindo'),
                onTap: () {
                  Navigator.pop(context);
                  _downloadCurrentSegment();
                },
              ),
              ListTile(
                leading: const Icon(Icons.content_cut),
                title: const Text('Criar clip'),
                subtitle: const Text('Recortar uma parte da gravacao'),
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
                height: MediaQuery.of(context).padding.bottom + ZySpacing.base,
              ),
            ],
          ),
        );
      },
    );
  }

  void _downloadCurrentSegment() {
    if (_currentSegmentId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nenhum segmento selecionado')),
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Download iniciado...')),
    );

    // For now, just show the URL - full download will be implemented with path_provider
    final url =
        '${AppConfig.apiBaseUrl}${ApiPaths.segmentDownload(_currentSegmentId!)}';
    debugPrint('[Download] Segment URL: $url');
  }
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final ThemeData theme;

  const _InfoChip({
    required this.icon,
    required this.label,
    required this.theme,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: theme.colorScheme.outline),
        const SizedBox(width: 4),
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.outline,
          ),
        ),
      ],
    );
  }
}
