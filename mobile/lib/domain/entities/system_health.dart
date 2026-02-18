import 'package:freezed_annotation/freezed_annotation.dart';

part 'system_health.freezed.dart';

@freezed
class SystemHealth with _$SystemHealth {
  const factory SystemHealth({
    @Default([0.0, 0.0, 0.0]) List<double> cpuLoad,
    @Default(0.0) double memoryUsageMb,
    @Default(0.0) double memoryPeakMb,
    @Default(0.0) double diskTotalGb,
    @Default(0.0) double diskFreeGb,
    @Default(0.0) double diskUsedPercent,
    @Default('') String phpVersion,
    @Default('') String laravelVersion,
    @Default('N/A') String uptime,
  }) = _SystemHealth;

  const SystemHealth._();

  double get diskUsedGb => diskTotalGb - diskFreeGb;

  String get cpuLoadDisplay {
    if (cpuLoad.isEmpty) return '0.00';
    return cpuLoad.first.toStringAsFixed(2);
  }

  String get cpuLoadFull {
    if (cpuLoad.length < 3) return cpuLoadDisplay;
    return '1m: ${cpuLoad[0].toStringAsFixed(2)} / '
        '5m: ${cpuLoad[1].toStringAsFixed(2)} / '
        '15m: ${cpuLoad[2].toStringAsFixed(2)}';
  }
}

@freezed
class WorkersInfo with _$WorkersInfo {
  const factory WorkersInfo({
    @Default(0) int transcoderCount,
    @Default(0) int transcoderHealthy,
    @Default(0) int transcoderCapacityPercent,
    @Default('unknown') String queueStatus,
    @Default(0) int queueProcesses,
    @Default(0) int queueJobsPending,
    @Default(0) int queueRecentFailed,
    @Default(0) int alertsCount,
  }) = _WorkersInfo;

  const WorkersInfo._();

  bool get isHealthy =>
      queueStatus == 'running' && alertsCount == 0;

  String get statusLabel {
    if (isHealthy) return 'Saudavel';
    if (queueStatus == 'inactive') return 'Inativo';
    if (alertsCount > 0) return 'Alertas';
    return 'Desconhecido';
  }
}
