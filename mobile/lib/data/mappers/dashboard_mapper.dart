import '../../domain/entities/analytics_event_summary.dart';
import '../../domain/entities/camera_summary.dart';
import '../../domain/entities/dashboard_data.dart';
import '../../domain/entities/dashboard_stats.dart';
import '../../domain/entities/plan_info.dart';
import '../../domain/entities/resource_quota.dart';
import '../../domain/entities/system_health.dart';
import '../../domain/enums/camera_status.dart';
import '../models/dashboard_model.dart';

class DashboardMapper {
  DashboardMapper._();

  static DashboardData toEntity(DashboardDataModel model) {
    return DashboardData(
      role: model.role,
      stats: _mapStats(model.stats),
      cameras: model.cameras.map(_mapCamera).toList(),
      recentEvents: model.recentEvents.map(_mapEvent).toList(),
      syncedAt:
          model.syncedAt != null ? DateTime.tryParse(model.syncedAt!) : null,
      quotas: model.quotas != null ? _mapQuotas(model.quotas!) : null,
      plan: model.plan != null ? _mapPlan(model.plan!) : null,
      systemHealth: model.systemHealth != null
          ? _mapSystemHealth(model.systemHealth!)
          : null,
      workers:
          model.workers != null ? _mapWorkers(model.workers!) : null,
    );
  }

  static DashboardStats _mapStats(DashboardStatsModel model) {
    return DashboardStats(
      totalCameras: model.totalCameras,
      onlineCameras: model.onlineCameras,
      offlineCameras: model.offlineCameras,
      recordingCameras: model.recordingCameras,
      activeStreams: model.activeStreams,
      storageUsedGb: model.storageUsedGb,
      storageTotalGb: model.storageTotalGb,
      totalDevices: model.totalDevices,
      devicesOnline: model.devicesOnline,
      totalTenants: model.totalTenants,
      tenantsActive: model.tenantsActive,
      totalUsers: model.totalUsers,
    );
  }

  static DashboardQuotas _mapQuotas(DashboardQuotasModel model) {
    return DashboardQuotas(
      cameras: model.cameras != null
          ? ResourceQuota(
              used: model.cameras!.used,
              limit: model.cameras!.limit,
            )
          : const ResourceQuota(),
      users: model.users != null
          ? ResourceQuota(
              used: model.users!.used,
              limit: model.users!.limit,
            )
          : const ResourceQuota(),
      storage: model.storageGb != null
          ? StorageQuota(
              usedGb: model.storageGb!.used,
              limitGb: model.storageGb!.limit,
            )
          : const StorageQuota(),
      streams: model.streams != null
          ? ResourceQuota(
              used: model.streams!.used,
              limit: model.streams!.limit,
            )
          : const ResourceQuota(),
    );
  }

  static PlanInfo _mapPlan(DashboardPlanModel model) {
    return PlanInfo(
      name: model.name,
      price: model.price,
      billingCycle: model.billingCycle,
      maxCameras: model.maxCameras,
      maxUsers: model.maxUsers,
      maxStorageGb: model.maxStorageGb,
      retentionDays: model.retentionDays,
      maxConcurrentStreams: model.maxConcurrentStreams,
      maxResolution: model.maxResolution,
      maxFps: model.maxFps,
      hasAiDetection: model.hasAiDetection,
      hasMobileApp: model.hasMobileApp,
      hasApiAccess: model.hasApiAccess,
    );
  }

  static SystemHealth _mapSystemHealth(DashboardSystemHealthModel model) {
    return SystemHealth(
      cpuLoad: model.cpuLoad,
      memoryUsageMb: model.memoryUsageMb,
      memoryPeakMb: model.memoryPeakMb,
      diskTotalGb: model.diskTotalGb,
      diskFreeGb: model.diskFreeGb,
      diskUsedPercent: model.diskUsedPercent,
      phpVersion: model.phpVersion,
      laravelVersion: model.laravelVersion,
      uptime: model.uptime,
    );
  }

  static WorkersInfo _mapWorkers(DashboardWorkersModel model) {
    return WorkersInfo(
      transcoderCount: model.transcoderCount,
      transcoderHealthy: model.transcoderHealthy,
      transcoderCapacityPercent: model.transcoderCapacityPercent,
      queueStatus: model.queueStatus,
      queueProcesses: model.queueProcesses,
      queueJobsPending: model.queueJobsPending,
      queueRecentFailed: model.queueRecentFailed,
      alertsCount: model.alertsCount,
    );
  }

  static CameraSummary _mapCamera(DashboardCameraModel model) {
    return CameraSummary(
      id: model.id,
      name: model.name,
      status: model.isOnline ? CameraStatus.online : CameraStatus.offline,
      isOnline: model.isOnline,
      isRecording: model.isRecording,
      snapshotUrl: model.snapshotUrl,
      hlsPath: model.hlsPath,
      manufacturer: model.manufacturer,
      location: model.location,
    );
  }

  static AnalyticsEventSummary _mapEvent(DashboardEventModel model) {
    return AnalyticsEventSummary(
      id: model.id,
      type: model.type,
      cameraId: model.cameraId,
      cameraName: model.cameraName,
      startedAt: model.startedAt != null
          ? DateTime.tryParse(model.startedAt!)
          : null,
      endedAt:
          model.endedAt != null ? DateTime.tryParse(model.endedAt!) : null,
      durationSeconds: model.durationSeconds,
    );
  }
}
