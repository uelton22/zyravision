import 'package:freezed_annotation/freezed_annotation.dart';

import 'analytics_event_summary.dart';
import 'camera_summary.dart';
import 'dashboard_stats.dart';

part 'dashboard_data.freezed.dart';

@freezed
class DashboardData with _$DashboardData {
  const factory DashboardData({
    required DashboardStats stats,
    @Default([]) List<CameraSummary> cameras,
    @Default([]) List<AnalyticsEventSummary> recentEvents,
    DateTime? syncedAt,
  }) = _DashboardData;
}
