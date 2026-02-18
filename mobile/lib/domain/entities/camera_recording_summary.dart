import 'package:freezed_annotation/freezed_annotation.dart';

part 'camera_recording_summary.freezed.dart';

@freezed
class CameraRecordingSummary with _$CameraRecordingSummary {
  const factory CameraRecordingSummary({
    required String cameraId,
    required String cameraName,
    String? cameraSlug,
    String? cameraStatus,
    @Default(false) bool isOnline,
    @Default(false) bool isRecording,
    @Default(0) int totalSegments,
    @Default(0) int totalDurationSeconds,
    String? totalDurationHuman,
    @Default(0.0) double totalHours,
    @Default(0) int totalSizeBytes,
    String? totalSizeHuman,
    @Default(0) int totalDays,
    DateTime? firstRecordingAt,
    DateTime? lastRecordingAt,
  }) = _CameraRecordingSummary;

  const CameraRecordingSummary._();

  bool get hasRecordings => totalSegments > 0;

  String get dateRange {
    if (firstRecordingAt == null || lastRecordingAt == null) return '--';
    final first = firstRecordingAt!;
    final last = lastRecordingAt!;
    return '${first.day.toString().padLeft(2, '0')}/${first.month.toString().padLeft(2, '0')} - '
        '${last.day.toString().padLeft(2, '0')}/${last.month.toString().padLeft(2, '0')}';
  }
}
