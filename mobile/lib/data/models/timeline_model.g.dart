// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'timeline_model.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TimelineResponseModel _$TimelineResponseModelFromJson(
  Map<String, dynamic> json,
) => TimelineResponseModel(
  cameraId: json['camera_id'] as String,
  range: TimelineRangeModel.fromJson(json['range'] as Map<String, dynamic>),
  segments:
      (json['segments'] as List<dynamic>)
          .map((e) => TimelineSegmentModel.fromJson(e as Map<String, dynamic>))
          .toList(),
  ranges:
      (json['ranges'] as List<dynamic>)
          .map((e) => TimelineRangeModel.fromJson(e as Map<String, dynamic>))
          .toList(),
  totalSegments: (json['total_segments'] as num?)?.toInt() ?? 0,
  totalDurationSeconds: (json['total_duration_seconds'] as num?)?.toInt() ?? 0,
);

TimelineSegmentModel _$TimelineSegmentModelFromJson(
  Map<String, dynamic> json,
) => TimelineSegmentModel(
  id: json['id'] as String,
  startsAt: json['starts_at'] as String,
  endsAt: json['ends_at'] as String?,
  durationSeconds: (json['duration_seconds'] as num?)?.toInt() ?? 0,
  fileSizeBytes: (json['file_size_bytes'] as num?)?.toInt() ?? 0,
  status: json['status'] as String? ?? 'unknown',
  hasLocal: json['has_local'] as bool? ?? false,
  hasRemote: json['has_remote'] as bool? ?? false,
  thumbnailUrl: json['thumbnail_url'] as String?,
);

TimelineRangeModel _$TimelineRangeModelFromJson(Map<String, dynamic> json) =>
    TimelineRangeModel(
      start: json['start'] as String,
      end: json['end'] as String,
    );

PlaybackResponseModel _$PlaybackResponseModelFromJson(
  Map<String, dynamic> json,
) => PlaybackResponseModel(
  found: json['found'] as bool,
  message: json['message'] as String?,
  segment:
      json['segment'] == null
          ? null
          : PlaybackSegmentModel.fromJson(
            json['segment'] as Map<String, dynamic>,
          ),
  offsetSeconds: (json['offset_seconds'] as num?)?.toInt() ?? 0,
);

PlaybackSegmentModel _$PlaybackSegmentModelFromJson(
  Map<String, dynamic> json,
) => PlaybackSegmentModel(
  id: json['id'] as String,
  startsAt: json['starts_at'] as String,
  endsAt: json['ends_at'] as String?,
  durationSeconds: (json['duration_seconds'] as num?)?.toInt() ?? 0,
  streamUrl: json['stream_url'] as String,
);

AvailableDatesResponseModel _$AvailableDatesResponseModelFromJson(
  Map<String, dynamic> json,
) => AvailableDatesResponseModel(
  dates:
      (json['dates'] as List<dynamic>)
          .map((e) => AvailableDateModel.fromJson(e as Map<String, dynamic>))
          .toList(),
);

AvailableDateModel _$AvailableDateModelFromJson(Map<String, dynamic> json) =>
    AvailableDateModel(
      date: json['date'] as String,
      segmentCount: (json['segment_count'] as num?)?.toInt() ?? 0,
      totalHours: (json['total_hours'] as num?)?.toDouble() ?? 0.0,
    );
