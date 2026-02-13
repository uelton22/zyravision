import '../../domain/entities/analytics_event_summary.dart';
import '../../domain/entities/camera_summary.dart';
import '../../domain/entities/dashboard_data.dart';
import '../../domain/entities/dashboard_stats.dart';
import '../../domain/enums/camera_status.dart';
import '../models/dashboard_model.dart';

class DashboardMapper {
  DashboardMapper._();

  static DashboardData toEntity(DashboardDataModel model) {
    return DashboardData(
      stats: _mapStats(model.stats),
      cameras: model.cameras.map(_mapCamera).toList(),
      recentEvents: model.recentEvents.map(_mapEvent).toList(),
      syncedAt: model.syncedAt != null
          ? DateTime.tryParse(model.syncedAt!)
          : null,
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
      endedAt: model.endedAt != null
          ? DateTime.tryParse(model.endedAt!)
          : null,
      durationSeconds: model.durationSeconds,
    );
  }
}
