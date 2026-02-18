import 'package:json_annotation/json_annotation.dart';

part 'dashboard_model.g.dart';

@JsonSerializable(createToJson: false)
class DashboardResponseModel {
  final DashboardDataModel data;

  const DashboardResponseModel({
    required this.data,
  });

  factory DashboardResponseModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardResponseModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class DashboardDataModel {
  @JsonKey(defaultValue: 'tenant')
  final String role;
  final DashboardStatsModel stats;
  @JsonKey(defaultValue: [])
  final List<DashboardCameraModel> cameras;
  @JsonKey(name: 'recent_events', defaultValue: [])
  final List<DashboardEventModel> recentEvents;
  @JsonKey(name: 'synced_at')
  final String? syncedAt;
  // Tenant fields
  final DashboardQuotasModel? quotas;
  final DashboardPlanModel? plan;
  // Admin fields
  @JsonKey(name: 'system_health')
  final DashboardSystemHealthModel? systemHealth;
  final DashboardWorkersModel? workers;

  const DashboardDataModel({
    this.role = 'tenant',
    required this.stats,
    this.cameras = const [],
    this.recentEvents = const [],
    this.syncedAt,
    this.quotas,
    this.plan,
    this.systemHealth,
    this.workers,
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
  @JsonKey(name: 'storage_total_gb', defaultValue: 0.0)
  final double storageTotalGb;
  // Admin-only
  @JsonKey(name: 'total_devices', defaultValue: 0)
  final int totalDevices;
  @JsonKey(name: 'devices_online', defaultValue: 0)
  final int devicesOnline;
  @JsonKey(name: 'total_tenants', defaultValue: 0)
  final int totalTenants;
  @JsonKey(name: 'tenants_active', defaultValue: 0)
  final int tenantsActive;
  @JsonKey(name: 'total_users', defaultValue: 0)
  final int totalUsers;

  const DashboardStatsModel({
    this.totalCameras = 0,
    this.onlineCameras = 0,
    this.offlineCameras = 0,
    this.recordingCameras = 0,
    this.activeStreams = 0,
    this.storageUsedGb = 0.0,
    this.storageTotalGb = 0.0,
    this.totalDevices = 0,
    this.devicesOnline = 0,
    this.totalTenants = 0,
    this.tenantsActive = 0,
    this.totalUsers = 0,
  });

  factory DashboardStatsModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardStatsModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class DashboardQuotasModel {
  final DashboardQuotaItemModel? cameras;
  final DashboardQuotaItemModel? users;
  @JsonKey(name: 'storage_gb')
  final DashboardStorageQuotaModel? storageGb;
  final DashboardQuotaItemModel? streams;

  const DashboardQuotasModel({
    this.cameras,
    this.users,
    this.storageGb,
    this.streams,
  });

  factory DashboardQuotasModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardQuotasModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class DashboardQuotaItemModel {
  @JsonKey(defaultValue: 0)
  final int used;
  @JsonKey(defaultValue: 0)
  final int limit;

  const DashboardQuotaItemModel({this.used = 0, this.limit = 0});

  factory DashboardQuotaItemModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardQuotaItemModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class DashboardStorageQuotaModel {
  @JsonKey(defaultValue: 0.0)
  final double used;
  @JsonKey(defaultValue: 0.0)
  final double limit;

  const DashboardStorageQuotaModel({this.used = 0.0, this.limit = 0.0});

  factory DashboardStorageQuotaModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardStorageQuotaModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class DashboardPlanModel {
  final String name;
  @JsonKey(fromJson: _priceFromJson, defaultValue: 0.0)
  final double price;
  @JsonKey(name: 'billing_cycle', defaultValue: 'monthly')
  final String billingCycle;
  @JsonKey(name: 'max_cameras', defaultValue: 0)
  final int maxCameras;
  @JsonKey(name: 'max_users', defaultValue: 0)
  final int maxUsers;
  @JsonKey(name: 'max_storage_gb', defaultValue: 0)
  final int maxStorageGb;
  @JsonKey(name: 'retention_days', defaultValue: 0)
  final int retentionDays;
  @JsonKey(name: 'max_concurrent_streams', defaultValue: 0)
  final int maxConcurrentStreams;
  @JsonKey(name: 'max_resolution')
  final String? maxResolution;
  @JsonKey(name: 'max_fps', defaultValue: 0)
  final int maxFps;
  @JsonKey(name: 'has_ai_detection', defaultValue: false)
  final bool hasAiDetection;
  @JsonKey(name: 'has_mobile_app', defaultValue: false)
  final bool hasMobileApp;
  @JsonKey(name: 'has_api_access', defaultValue: false)
  final bool hasApiAccess;

  const DashboardPlanModel({
    required this.name,
    this.price = 0.0,
    this.billingCycle = 'monthly',
    this.maxCameras = 0,
    this.maxUsers = 0,
    this.maxStorageGb = 0,
    this.retentionDays = 0,
    this.maxConcurrentStreams = 0,
    this.maxResolution,
    this.maxFps = 0,
    this.hasAiDetection = false,
    this.hasMobileApp = false,
    this.hasApiAccess = false,
  });

  factory DashboardPlanModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardPlanModelFromJson(json);

  static double _priceFromJson(dynamic value) {
    if (value == null) return 0.0;
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
}

@JsonSerializable(createToJson: false)
class DashboardSystemHealthModel {
  @JsonKey(name: 'cpu_load', defaultValue: [0.0, 0.0, 0.0])
  final List<double> cpuLoad;
  @JsonKey(name: 'memory_usage_mb', defaultValue: 0.0)
  final double memoryUsageMb;
  @JsonKey(name: 'memory_peak_mb', defaultValue: 0.0)
  final double memoryPeakMb;
  @JsonKey(name: 'disk_total_gb', defaultValue: 0.0)
  final double diskTotalGb;
  @JsonKey(name: 'disk_free_gb', defaultValue: 0.0)
  final double diskFreeGb;
  @JsonKey(name: 'disk_used_percent', defaultValue: 0.0)
  final double diskUsedPercent;
  @JsonKey(name: 'php_version', defaultValue: '')
  final String phpVersion;
  @JsonKey(name: 'laravel_version', defaultValue: '')
  final String laravelVersion;
  @JsonKey(defaultValue: 'N/A')
  final String uptime;

  const DashboardSystemHealthModel({
    this.cpuLoad = const [0.0, 0.0, 0.0],
    this.memoryUsageMb = 0.0,
    this.memoryPeakMb = 0.0,
    this.diskTotalGb = 0.0,
    this.diskFreeGb = 0.0,
    this.diskUsedPercent = 0.0,
    this.phpVersion = '',
    this.laravelVersion = '',
    this.uptime = 'N/A',
  });

  factory DashboardSystemHealthModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardSystemHealthModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class DashboardWorkersModel {
  @JsonKey(name: 'transcoder_count', defaultValue: 0)
  final int transcoderCount;
  @JsonKey(name: 'transcoder_healthy', defaultValue: 0)
  final int transcoderHealthy;
  @JsonKey(name: 'transcoder_capacity_percent', defaultValue: 0)
  final int transcoderCapacityPercent;
  @JsonKey(name: 'queue_status', defaultValue: 'unknown')
  final String queueStatus;
  @JsonKey(name: 'queue_processes', defaultValue: 0)
  final int queueProcesses;
  @JsonKey(name: 'queue_jobs_pending', defaultValue: 0)
  final int queueJobsPending;
  @JsonKey(name: 'queue_recent_failed', defaultValue: 0)
  final int queueRecentFailed;
  @JsonKey(name: 'alerts_count', defaultValue: 0)
  final int alertsCount;

  const DashboardWorkersModel({
    this.transcoderCount = 0,
    this.transcoderHealthy = 0,
    this.transcoderCapacityPercent = 0,
    this.queueStatus = 'unknown',
    this.queueProcesses = 0,
    this.queueJobsPending = 0,
    this.queueRecentFailed = 0,
    this.alertsCount = 0,
  });

  factory DashboardWorkersModel.fromJson(Map<String, dynamic> json) =>
      _$DashboardWorkersModelFromJson(json);
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
