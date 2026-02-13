import 'package:freezed_annotation/freezed_annotation.dart';

part 'analytics_event_summary.freezed.dart';

@freezed
class AnalyticsEventSummary with _$AnalyticsEventSummary {
  const factory AnalyticsEventSummary({
    required String id,
    required String type,
    String? cameraId,
    String? cameraName,
    String? message,
    DateTime? startedAt,
    DateTime? endedAt,
    int? durationSeconds,
  }) = _AnalyticsEventSummary;
}
