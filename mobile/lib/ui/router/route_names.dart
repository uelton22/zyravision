class RouteNames {
  RouteNames._();

  static const splash = '/';
  static const login = '/login';
  static const dashboard = '/dashboard';
  static const cameras = '/cameras';
  static const cameraDetail = '/cameras/:id';
  static const liveView = '/cameras/:id/live';
  static const multiView = '/cameras/multi-view';
  static const alerts = '/alerts';
  static const settings = '/settings';

  static String cameraDetailPath(String id) => '/cameras/$id';
  static String liveViewPath(String id) => '/cameras/$id/live';
}
