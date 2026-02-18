import 'package:json_annotation/json_annotation.dart';

part 'camera_recording_summary_model.g.dart';

@JsonSerializable(createToJson: false)
class CameraRecordingSummaryListResponse {
  final List<CameraRecordingSummaryModel> data;

  const CameraRecordingSummaryListResponse({required this.data});

  factory CameraRecordingSummaryListResponse.fromJson(
          Map<String, dynamic> json) =>
      _$CameraRecordingSummaryListResponseFromJson(json);
}

@JsonSerializable(createToJson: false)
class CameraRecordingSummaryModel {
  @JsonKey(name: 'camera_id')
  final String cameraId;
  @JsonKey(name: 'camera_name')
  final String cameraName;
  @JsonKey(name: 'camera_slug')
  final String? cameraSlug;
  @JsonKey(name: 'camera_status')
  final String? cameraStatus;
  @JsonKey(name: 'is_online', defaultValue: false)
  final bool isOnline;
  @JsonKey(name: 'is_recording', defaultValue: false)
  final bool isRecording;
  @JsonKey(name: 'total_segments', defaultValue: 0)
  final int totalSegments;
  @JsonKey(name: 'total_duration_seconds', defaultValue: 0)
  final int totalDurationSeconds;
  @JsonKey(name: 'total_duration_human')
  final String? totalDurationHuman;
  @JsonKey(name: 'total_hours', defaultValue: 0.0)
  final double totalHours;
  @JsonKey(name: 'total_size_bytes', defaultValue: 0)
  final int totalSizeBytes;
  @JsonKey(name: 'total_size_human')
  final String? totalSizeHuman;
  @JsonKey(name: 'total_days', defaultValue: 0)
  final int totalDays;
  @JsonKey(name: 'first_recording_at')
  final String? firstRecordingAt;
  @JsonKey(name: 'last_recording_at')
  final String? lastRecordingAt;

  const CameraRecordingSummaryModel({
    required this.cameraId,
    required this.cameraName,
    this.cameraSlug,
    this.cameraStatus,
    required this.isOnline,
    required this.isRecording,
    required this.totalSegments,
    required this.totalDurationSeconds,
    this.totalDurationHuman,
    required this.totalHours,
    required this.totalSizeBytes,
    this.totalSizeHuman,
    required this.totalDays,
    this.firstRecordingAt,
    this.lastRecordingAt,
  });

  factory CameraRecordingSummaryModel.fromJson(Map<String, dynamic> json) =>
      _$CameraRecordingSummaryModelFromJson(json);
}
