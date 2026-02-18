// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'camera_recording_summary_model.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CameraRecordingSummaryListResponse _$CameraRecordingSummaryListResponseFromJson(
  Map<String, dynamic> json,
) => CameraRecordingSummaryListResponse(
  data:
      (json['data'] as List<dynamic>)
          .map(
            (e) =>
                CameraRecordingSummaryModel.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
);

CameraRecordingSummaryModel _$CameraRecordingSummaryModelFromJson(
  Map<String, dynamic> json,
) => CameraRecordingSummaryModel(
  cameraId: json['camera_id'] as String,
  cameraName: json['camera_name'] as String,
  cameraSlug: json['camera_slug'] as String?,
  cameraStatus: json['camera_status'] as String?,
  isOnline: json['is_online'] as bool? ?? false,
  isRecording: json['is_recording'] as bool? ?? false,
  totalSegments: (json['total_segments'] as num?)?.toInt() ?? 0,
  totalDurationSeconds: (json['total_duration_seconds'] as num?)?.toInt() ?? 0,
  totalDurationHuman: json['total_duration_human'] as String?,
  totalHours: (json['total_hours'] as num?)?.toDouble() ?? 0.0,
  totalSizeBytes: (json['total_size_bytes'] as num?)?.toInt() ?? 0,
  totalSizeHuman: json['total_size_human'] as String?,
  totalDays: (json['total_days'] as num?)?.toInt() ?? 0,
  firstRecordingAt: json['first_recording_at'] as String?,
  lastRecordingAt: json['last_recording_at'] as String?,
);
