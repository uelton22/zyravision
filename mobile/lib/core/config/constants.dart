class StorageKeys {
  StorageKeys._();

  static const String accessToken = 'access_token';
  static const String userId = 'user_id';
  static const String tenantId = 'tenant_id';
  static const String themeMode = 'theme_mode';
  static const String onboardingDone = 'onboarding_done';
}

class ApiPaths {
  ApiPaths._();

  // Auth
  static const String login = '/auth/login';
  static const String logout = '/auth/logout';
  static const String me = '/auth/me';
  static const String register = '/auth/register';

  // Dashboard
  static const String dashboard = '/mobile/dashboard';

  // Cameras
  static const String cameras = '/cameras';
  static String cameraDetail(String id) => '/cameras/$id';
  static String cameraSnapshot(String id) => '/cameras/$id/snapshot';
  static String cameraStreamStart(String id) => '/cameras/$id/stream/start';
  static String cameraStreamStop(String id) => '/cameras/$id/stream/stop';

  // Recordings
  static const String recordings = '/recordings';
  static String recordingDetail(String id) => '/recordings/$id';
  static String recordingTimeline(String cameraId) => '/recordings/timeline/$cameraId';

  // Alerts
  static const String events = '/analytics/events';

  // Storage
  static const String storageStats = '/storage/stats';

  // Profile
  static const String profile = '/profile';

  // User Devices
  static const String userDevices = '/user-devices';
  static const String registerDevice = '/user-devices/register';
}
