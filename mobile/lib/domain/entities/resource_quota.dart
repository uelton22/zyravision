import 'package:freezed_annotation/freezed_annotation.dart';

part 'resource_quota.freezed.dart';

@freezed
class ResourceQuota with _$ResourceQuota {
  const factory ResourceQuota({
    @Default(0) int used,
    @Default(0) int limit,
  }) = _ResourceQuota;

  const ResourceQuota._();

  double get percentage {
    if (limit <= 0) return 0;
    return (used / limit * 100).clamp(0, 100);
  }

  double get fraction {
    if (limit <= 0) return 0;
    return (used / limit).clamp(0, 1);
  }

  String get display => '$used / $limit';
}

@freezed
class StorageQuota with _$StorageQuota {
  const factory StorageQuota({
    @Default(0.0) double usedGb,
    @Default(0.0) double limitGb,
  }) = _StorageQuota;

  const StorageQuota._();

  double get percentage {
    if (limitGb <= 0) return 0;
    return (usedGb / limitGb * 100).clamp(0, 100);
  }

  double get fraction {
    if (limitGb <= 0) return 0;
    return (usedGb / limitGb).clamp(0, 1);
  }

  String get display =>
      '${usedGb.toStringAsFixed(1)} / ${limitGb.toStringAsFixed(0)} GB';
}

@freezed
class DashboardQuotas with _$DashboardQuotas {
  const factory DashboardQuotas({
    @Default(ResourceQuota()) ResourceQuota cameras,
    @Default(ResourceQuota()) ResourceQuota users,
    @Default(StorageQuota()) StorageQuota storage,
    @Default(ResourceQuota()) ResourceQuota streams,
  }) = _DashboardQuotas;
}
