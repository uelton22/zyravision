import 'package:json_annotation/json_annotation.dart';

part 'dashboard_model.g.dart';

@JsonSerializable(createToJson: false)
class DashboardResponseModel {
  final bool success;
  final DashboardDataModel data;

  const DashboardResponseModel({
    required this.success,
    required this.data,
  });

  factory DashboardResponseModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardResponseModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class DashboardDataModel {
  final DashboardStatsModel stats;
  @JsonKey(defaultValue: [])
  final List<DashboardCameraModel> cameras;
  @JsonKey(name: 'recent_events', defaultValue: [])
  final List<DashboardEventModel> recentEvents;
  @JsonKey(name: 'synced_at')
  final String? syncedAt;

  const DashboardDataModel({
    required this.stats,
    this.cameras = const [],
    this.recentEvents = const [],
    this.syncedAt,
  });

  factory DashboardDataModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardDataModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class DashboardStatsModel {
  @JsonKey(name: 'total_cameras', defaultValue: 0)
  final int totalCameras;
  @JsonKey(name: 'online_cameras', defaultValue: 0)
  final int onlineCameras;
  @JsonKey(name: 'offline_cameras', defaultValue: 0)
  final int offlineCameras;
  @JsonKey(name: 'recording_cameras', defaultValue: 0)
  final int recordingCameras;
  @JsonKey(name: 'active_streams', defaultValue: 0)
  final int activeStreams;
  @JsonKey(name: 'storage_used_gb', defaultValue: 0.0)
  final double storageUsedGb;

  const DashboardStatsModel({
    this.totalCameras = 0,
    this.onlineCameras = 0,
    this.offlineCameras = 0,
    this.recordingCameras = 0,
    this.activeStreams = 0,
    this.storageUsedGb = 0.0,
  });

  factory DashboardStatsModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardStatsModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class DashboardCameraModel {
  final String id;
  final String name;
  final String? slug;
  @JsonKey(name: 'is_active', defaultValue: false)
  final bool isActive;
  @JsonKey(name: 'is_online', defaultValue: false)
  final bool isOnline;
  @JsonKey(name: 'is_recording', defaultValue: false)
  final bool isRecording;
  final String? manufacturer;
  final String? location;
  @JsonKey(name: 'snapshot_url')
  final String? snapshotUrl;
  @JsonKey(name: 'hls_path')
  final String? hlsPath;

  const DashboardCameraModel({
    required this.id,
    required this.name,
    this.slug,
    this.isActive = false,
    this.isOnline = false,
    this.isRecording = false,
    this.manufacturer,
    this.location,
    this.snapshotUrl,
    this.hlsPath,
  });

  factory DashboardCameraModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardCameraModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class DashboardEventModel {
  final String id;
  final String type;
  @JsonKey(name: 'camera_id')
  final String? cameraId;
  @JsonKey(name: 'camera_name')
  final String? cameraName;
  @JsonKey(name: 'started_at')
  final String? startedAt;
  @JsonKey(name: 'ended_at')
  final String? endedAt;
  @JsonKey(name: 'duration_seconds')
  final int? durationSeconds;

  const DashboardEventModel({
    required this.id,
    required this.type,
    this.cameraId,
    this.cameraName,
    this.startedAt,
    this.endedAt,
    this.durationSeconds,
  });

  factory DashboardEventModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardEventModelFromJson(json);
}
