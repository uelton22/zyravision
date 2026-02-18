import 'package:freezed_annotation/freezed_annotation.dart';

import 'analytics_event_summary.dart';
import 'camera_summary.dart';
import 'dashboard_stats.dart';
import 'plan_info.dart';
import 'resource_quota.dart';
import 'system_health.dart';

part 'dashboard_data.freezed.dart';

@freezed
class DashboardData with _$DashboardData {
  const factory DashboardData({
    required String role,
    required DashboardStats stats,
    @Default([]) List<CameraSummary> cameras,
    @Default([]) List<AnalyticsEventSummary> recentEvents,
    DateTime? syncedAt,
    // Tenant-only fields
    DashboardQuotas? quotas,
    PlanInfo? plan,
    // Admin-only fields
    SystemHealth? systemHealth,
    WorkersInfo? workers,
  }) = _DashboardData;

  const DashboardData._();

  bool get isSuperAdmin => role == 'super-admin';
  bool get isTenant => role == 'tenant';
  bool get hasPlan => plan != null;
  bool get hasQuotas => quotas != null;
  bool get hasSystemHealth => systemHealth != null;
  bool get hasWorkers => workers != null;
}
