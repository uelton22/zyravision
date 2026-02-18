class RouteNames {
  RouteNames._();

  static const splash = '/';
  static const login = '/login';
  static const dashboard = '/dashboard';
  static const cameras = '/cameras';
  static const cameraDetail = '/cameras/:id';
  static const liveView = '/cameras/:id/live';
  static const multiView = '/cameras/multi-view';
  static const recordings = '/recordings';
  static const recordingPlayback = '/recordings/:cameraId/playback';
  static const alerts = '/alerts';
  static const settings = '/settings';

  static String cameraDetailPath(String id) => '/cameras/$id';
  static String liveViewPath(String id) => '/cameras/$id/live';
  static String recordingPlaybackPath(
    String cameraId, {
    String? recordingId,
    String? cameraName,
  }) {
    final base = '/recordings/$cameraId/playback';
    final params = <String>[];
    if (recordingId != null) params.add('recording=$recordingId');
    if (cameraName != null) {
      params.add('name=${Uri.encodeComponent(cameraName)}');
    }
    if (params.isEmpty) return base;
    return '$base?${params.join('&')}';
  }
}
