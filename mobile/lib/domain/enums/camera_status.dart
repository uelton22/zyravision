enum CameraStatus {
  online,
  offline,
  error,
  unknown;

  static CameraStatus fromString(String value) {
    return CameraStatus.values.firstWhere(
      (e) => e.name == value.toLowerCase(),
      orElse: () => CameraStatus.unknown,
    );
  }

  bool get isOnline => this == CameraStatus.online;
  bool get isOffline => this == CameraStatus.offline;
}
