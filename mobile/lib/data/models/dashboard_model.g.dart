// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'dashboard_model.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DashboardResponseModel _$DashboardResponseModelFromJson(
  Map<String, dynamic> json,
) => DashboardResponseModel(
  data: DashboardDataModel.fromJson(json['data'] as Map<String, dynamic>),
);

DashboardDataModel _$DashboardDataModelFromJson(
  Map<String, dynamic> json,
) => DashboardDataModel(
  role: json['role'] as String? ?? 'tenant',
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
  quotas:
      json['quotas'] == null
          ? null
          : DashboardQuotasModel.fromJson(
            json['quotas'] as Map<String, dynamic>,
          ),
  plan:
      json['plan'] == null
          ? null
          : DashboardPlanModel.fromJson(json['plan'] as Map<String, dynamic>),
  systemHealth:
      json['system_health'] == null
          ? null
          : DashboardSystemHealthModel.fromJson(
            json['system_health'] as Map<String, dynamic>,
          ),
  workers:
      json['workers'] == null
          ? null
          : DashboardWorkersModel.fromJson(
            json['workers'] as Map<String, dynamic>,
          ),
);

DashboardStatsModel _$DashboardStatsModelFromJson(Map<String, dynamic> json) =>
    DashboardStatsModel(
      totalCameras: (json['total_cameras'] as num?)?.toInt() ?? 0,
      onlineCameras: (json['online_cameras'] as num?)?.toInt() ?? 0,
      offlineCameras: (json['offline_cameras'] as num?)?.toInt() ?? 0,
      recordingCameras: (json['recording_cameras'] as num?)?.toInt() ?? 0,
      activeStreams: (json['active_streams'] as num?)?.toInt() ?? 0,
      storageUsedGb: (json['storage_used_gb'] as num?)?.toDouble() ?? 0.0,
      storageTotalGb: (json['storage_total_gb'] as num?)?.toDouble() ?? 0.0,
      totalDevices: (json['total_devices'] as num?)?.toInt() ?? 0,
      devicesOnline: (json['devices_online'] as num?)?.toInt() ?? 0,
      totalTenants: (json['total_tenants'] as num?)?.toInt() ?? 0,
      tenantsActive: (json['tenants_active'] as num?)?.toInt() ?? 0,
      totalUsers: (json['total_users'] as num?)?.toInt() ?? 0,
    );

DashboardQuotasModel _$DashboardQuotasModelFromJson(
  Map<String, dynamic> json,
) => DashboardQuotasModel(
  cameras:
      json['cameras'] == null
          ? null
          : DashboardQuotaItemModel.fromJson(
            json['cameras'] as Map<String, dynamic>,
          ),
  users:
      json['users'] == null
          ? null
          : DashboardQuotaItemModel.fromJson(
            json['users'] as Map<String, dynamic>,
          ),
  storageGb:
      json['storage_gb'] == null
          ? null
          : DashboardStorageQuotaModel.fromJson(
            json['storage_gb'] as Map<String, dynamic>,
          ),
  streams:
      json['streams'] == null
          ? null
          : DashboardQuotaItemModel.fromJson(
            json['streams'] as Map<String, dynamic>,
          ),
);

DashboardQuotaItemModel _$DashboardQuotaItemModelFromJson(
  Map<String, dynamic> json,
) => DashboardQuotaItemModel(
  used: (json['used'] as num?)?.toInt() ?? 0,
  limit: (json['limit'] as num?)?.toInt() ?? 0,
);

DashboardStorageQuotaModel _$DashboardStorageQuotaModelFromJson(
  Map<String, dynamic> json,
) => DashboardStorageQuotaModel(
  used: (json['used'] as num?)?.toDouble() ?? 0.0,
  limit: (json['limit'] as num?)?.toDouble() ?? 0.0,
);

DashboardPlanModel _$DashboardPlanModelFromJson(Map<String, dynamic> json) =>
    DashboardPlanModel(
      name: json['name'] as String,
      price:
          json['price'] == null
              ? 0.0
              : DashboardPlanModel._priceFromJson(json['price']),
      billingCycle: json['billing_cycle'] as String? ?? 'monthly',
      maxCameras: (json['max_cameras'] as num?)?.toInt() ?? 0,
      maxUsers: (json['max_users'] as num?)?.toInt() ?? 0,
      maxStorageGb: (json['max_storage_gb'] as num?)?.toInt() ?? 0,
      retentionDays: (json['retention_days'] as num?)?.toInt() ?? 0,
      maxConcurrentStreams:
          (json['max_concurrent_streams'] as num?)?.toInt() ?? 0,
      maxResolution: json['max_resolution'] as String?,
      maxFps: (json['max_fps'] as num?)?.toInt() ?? 0,
      hasAiDetection: json['has_ai_detection'] as bool? ?? false,
      hasMobileApp: json['has_mobile_app'] as bool? ?? false,
      hasApiAccess: json['has_api_access'] as bool? ?? false,
    );

DashboardSystemHealthModel _$DashboardSystemHealthModelFromJson(
  Map<String, dynamic> json,
) => DashboardSystemHealthModel(
  cpuLoad:
      (json['cpu_load'] as List<dynamic>?)
          ?.map((e) => (e as num).toDouble())
          .toList() ??
      [0.0, 0.0, 0.0],
  memoryUsageMb: (json['memory_usage_mb'] as num?)?.toDouble() ?? 0.0,
  memoryPeakMb: (json['memory_peak_mb'] as num?)?.toDouble() ?? 0.0,
  diskTotalGb: (json['disk_total_gb'] as num?)?.toDouble() ?? 0.0,
  diskFreeGb: (json['disk_free_gb'] as num?)?.toDouble() ?? 0.0,
  diskUsedPercent: (json['disk_used_percent'] as num?)?.toDouble() ?? 0.0,
  phpVersion: json['php_version'] as String? ?? '',
  laravelVersion: json['laravel_version'] as String? ?? '',
  uptime: json['uptime'] as String? ?? 'N/A',
);

DashboardWorkersModel _$DashboardWorkersModelFromJson(
  Map<String, dynamic> json,
) => DashboardWorkersModel(
  transcoderCount: (json['transcoder_count'] as num?)?.toInt() ?? 0,
  transcoderHealthy: (json['transcoder_healthy'] as num?)?.toInt() ?? 0,
  transcoderCapacityPercent:
      (json['transcoder_capacity_percent'] as num?)?.toInt() ?? 0,
  queueStatus: json['queue_status'] as String? ?? 'unknown',
  queueProcesses: (json['queue_processes'] as num?)?.toInt() ?? 0,
  queueJobsPending: (json['queue_jobs_pending'] as num?)?.toInt() ?? 0,
  queueRecentFailed: (json['queue_recent_failed'] as num?)?.toInt() ?? 0,
  alertsCount: (json['alerts_count'] as num?)?.toInt() ?? 0,
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
