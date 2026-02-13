import 'package:freezed_annotation/freezed_annotation.dart';

part 'dashboard_stats.freezed.dart';

@freezed
class DashboardStats with _$DashboardStats {
  const factory DashboardStats({
    @Default(0) int totalCameras,
    @Default(0) int onlineCameras,
    @Default(0) int offlineCameras,
    @Default(0) int recordingCameras,
    @Default(0) int activeStreams,
    @Default(0.0) double storageUsedGb,
    @Default(0.0) double storageTotalGb,
  }) = _DashboardStats;

  const DashboardStats._();

  double get storagePercentage {
    if (storageTotalGb <= 0) return 0;
    return (storageUsedGb / storageTotalGb * 100).clamp(0, 100);
  }

  double get storageFraction {
    if (storageTotalGb <= 0) return 0;
    return (storageUsedGb / storageTotalGb).clamp(0, 1);
  }
}
