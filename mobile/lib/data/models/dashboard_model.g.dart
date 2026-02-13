// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'dashboard_model.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DashboardResponseModel _$DashboardResponseModelFromJson(
  Map<String, dynamic> json,
) => DashboardResponseModel(
  success: json['success'] as bool,
  data: DashboardDataModel.fromJson(json['data'] as Map<String, dynamic>),
);

DashboardDataModel _$DashboardDataModelFromJson(
  Map<String, dynamic> json,
) => DashboardDataModel(
  stats: DashboardStatsModel.fromJson(json['stats'] as Map<String, dynamic>),
  cameras:
      (json['cameras'] as List<dynamic>?)
          ?.map((e) => DashboardCameraModel.fromJson(e as Map<String, dynamic>))
          .toList() ??
      [],
  recentEvents:
      (json['recent_events'] as List<dynamic>?)
          ?.map((e) => DashboardEventModel.fromJson(e as Map<String, dynamic>))
          .toList() ??
      [],
  syncedAt: json['synced_at'] as String?,
);

DashboardStatsModel _$DashboardStatsModelFromJson(Map<String, dynamic> json) =>
    DashboardStatsModel(
      totalCameras: (json['total_cameras'] as num?)?.toInt() ?? 0,
      onlineCameras: (json['online_cameras'] as num?)?.toInt() ?? 0,
      offlineCameras: (json['offline_cameras'] as num?)?.toInt() ?? 0,
      recordingCameras: (json['recording_cameras'] as num?)?.toInt() ?? 0,
      activeStreams: (json['active_streams'] as num?)?.toInt() ?? 0,
      storageUsedGb: (json['storage_used_gb'] as num?)?.toDouble() ?? 0.0,
    );

DashboardCameraModel _$DashboardCameraModelFromJson(
  Map<String, dynamic> json,
) => DashboardCameraModel(
  id: json['id'] as String,
  name: json['name'] as String,
  slug: json['slug'] as String?,
  isActive: json['is_active'] as bool? ?? false,
  isOnline: json['is_online'] as bool? ?? false,
  isRecording: json['is_recording'] as bool? ?? false,
  manufacturer: json['manufacturer'] as String?,
  location: json['location'] as String?,
  snapshotUrl: json['snapshot_url'] as String?,
  hlsPath: json['hls_path'] as String?,
);

DashboardEventModel _$DashboardEventModelFromJson(Map<String, dynamic> json) =>
    DashboardEventModel(
      id: json['id'] as String,
      type: json['type'] as String,
      cameraId: json['camera_id'] as String?,
      cameraName: json['camera_name'] as String?,
      startedAt: json['started_at'] as String?,
      endedAt: json['ended_at'] as String?,
      durationSeconds: (json['duration_seconds'] as num?)?.toInt(),
    );
