import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:media_kit/media_kit.dart';
import 'package:media_kit_video/media_kit_video.dart';

import '../../../core/theme/zy_spacing.dart';
import '../../../core/utils/url_helper.dart';
import '../../../domain/entities/camera.dart';
import '../../../state/camera/camera_providers.dart';
import '../../router/route_names.dart';
import '../../widgets/feedback/zy_badge.dart';

class MultiViewScreen extends ConsumerStatefulWidget {
  final List<String> cameraIds;

  const MultiViewScreen({super.key, required this.cameraIds});

  @override
  ConsumerState<MultiViewScreen> createState() => _MultiViewScreenState();
}

class _MultiViewScreenState extends ConsumerState<MultiViewScreen> {
  final Map<String, Player> _players = {};
  final Map<String, VideoController> _controllers = {};
  bool _isFullscreen = false;

  @override
  void initState() {
    super.initState();
    _initPlayers();
  }

  void _initPlayers() {
    for (final id in widget.cameraIds.take(4)) {
      final player = Player(
        configuration: const PlayerConfiguration(
          logLevel: MPVLogLevel.error,
          bufferSize: 2 * 1024 * 1024,
        ),
      );
      final mpv = player.platform as dynamic;
      mpv.setProperty('cache', 'no');
      mpv.setProperty('cache-secs', '3');
      mpv.setProperty('demuxer-lavf-o', 'fflags=+nobuffer');
      mpv.setProperty('profile', 'low-latency');
      mpv.setProperty('untimed', '');
      mpv.setProperty('force-seekable', 'yes');
      _players[id] = player;
      _controllers[id] = VideoController(
        player,
        configuration: const VideoControllerConfiguration(
          enableHardwareAcceleration: true,
        ),
      );
    }
  }

  void _startStreams(Map<String, Camera> cameras) {
    for (final entry in cameras.entries) {
      final cam = entry.value;
      // Tenta urls.hls do active_stream, senao constroi do mediamtx_path
      String? url = UrlHelper.hlsUrlFromPath(cam.stream?.urls?.hls);
      url ??= UrlHelper.hlsUrl(cam.stream?.mediamtxPath);

      if (url != null && _players.containsKey(entry.key)) {
        final player = _players[entry.key]!;
        if (player.state.playlist.medias.isEmpty) {
          player.open(Media(url));
        }
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
      SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
    }
  }

  @override
  void dispose() {
    for (final player in _players.values) {
      player.dispose();
    }
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // Load camera data
    final cameras = <String, Camera>{};
    for (final id in widget.cameraIds.take(4)) {
      final data = ref.watch(cameraDetailProvider(id));
      data.whenData((cam) => cameras[id] = cam);
    }

    // Start streams once data is available
    if (cameras.isNotEmpty) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _startStreams(cameras);
      });
    }

    if (_isFullscreen) {
      return Scaffold(
        backgroundColor: Colors.black,
        body: GestureDetector(
          onTap: _toggleFullscreen,
          child: _buildGrid(cameras),
        ),
      );
    }

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go(RouteNames.cameras),
        ),
        title: Text('Multi-View (${widget.cameraIds.length})'),
        actions: [
          IconButton(
            icon: const Icon(Icons.fullscreen_rounded),
            tooltip: 'Tela cheia',
            onPressed: _toggleFullscreen,
          ),
        ],
      ),
      body: _buildGrid(cameras),
    );
  }

  Widget _buildGrid(Map<String, Camera> cameras) {
    final ids = widget.cameraIds.take(4).toList();

    return GridView.builder(
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 2,
        mainAxisSpacing: 2,
        childAspectRatio: 16 / 9,
      ),
      itemCount: ids.length,
      itemBuilder: (context, index) {
        final id = ids[index];
        final camera = cameras[id];
        final controller = _controllers[id];

        return GestureDetector(
          onDoubleTap: () {
            if (camera != null) {
              final encodedName = Uri.encodeComponent(camera.name);
              context.go(
                '${RouteNames.liveViewPath(id)}?name=$encodedName',
              );
            }
          },
          child: _MultiViewCell(
            camera: camera,
            controller: controller,
            cameraId: id,
          ),
        );
      },
    );
  }
}

class _MultiViewCell extends StatelessWidget {
  final Camera? camera;
  final VideoController? controller;
  final String cameraId;

  const _MultiViewCell({
    this.camera,
    this.controller,
    required this.cameraId,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.black,
      child: Stack(
        fit: StackFit.expand,
        children: [
          if (controller != null)
            Video(
              controller: controller!,
              controls: NoVideoControls,
              fill: Colors.black,
            )
          else
            const Center(
              child: CircularProgressIndicator(
                color: Colors.white38,
                strokeWidth: 2,
              ),
            ),
          // Camera name overlay
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: const EdgeInsets.symmetric(
                horizontal: ZySpacing.sm,
                vertical: ZySpacing.xs,
              ),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.bottomCenter,
                  end: Alignment.topCenter,
                  colors: [Colors.black54, Colors.transparent],
                ),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      camera?.name ?? 'Carregando...',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (camera?.isOnline == true)
                    const ZyBadge(
                      label: 'LIVE',
                      variant: ZyBadgeVariant.success,
                      size: ZyBadgeSize.sm,
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
