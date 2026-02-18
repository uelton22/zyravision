import 'package:json_annotation/json_annotation.dart';

part 'timeline_model.g.dart';

@JsonSerializable(createToJson: false)
class TimelineResponseModel {
  @JsonKey(name: 'camera_id')
  final String cameraId;
  final TimelineRangeModel range;
  final List<TimelineSegmentModel> segments;
  final List<TimelineRangeModel> ranges;
  @JsonKey(name: 'total_segments', defaultValue: 0)
  final int totalSegments;
  @JsonKey(name: 'total_duration_seconds', defaultValue: 0)
  final int totalDurationSeconds;

  const TimelineResponseModel({
    required this.cameraId,
    required this.range,
    required this.segments,
    required this.ranges,
    required this.totalSegments,
    required this.totalDurationSeconds,
  });

  factory TimelineResponseModel.fromJson(Map<String, dynamic> json) =>
      _$TimelineResponseModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class TimelineSegmentModel {
  final String id;
  @JsonKey(name: 'starts_at')
  final String startsAt;
  @JsonKey(name: 'ends_at')
  final String? endsAt;
  @JsonKey(name: 'duration_seconds', defaultValue: 0)
  final int durationSeconds;
  @JsonKey(name: 'file_size_bytes', defaultValue: 0)
  final int fileSizeBytes;
  @JsonKey(defaultValue: 'unknown')
  final String status;
  @JsonKey(name: 'has_local', defaultValue: false)
  final bool hasLocal;
  @JsonKey(name: 'has_remote', defaultValue: false)
  final bool hasRemote;

  const TimelineSegmentModel({
    required this.id,
    required this.startsAt,
    this.endsAt,
    required this.durationSeconds,
    required this.fileSizeBytes,
    required this.status,
    required this.hasLocal,
    required this.hasRemote,
  });

  factory TimelineSegmentModel.fromJson(Map<String, dynamic> json) =>
      _$TimelineSegmentModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class TimelineRangeModel {
  final String start;
  final String end;

  const TimelineRangeModel({
    required this.start,
    required this.end,
  });

  factory TimelineRangeModel.fromJson(Map<String, dynamic> json) =>
      _$TimelineRangeModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class PlaybackResponseModel {
  final bool found;
  final String? message;
  final PlaybackSegmentModel? segment;
  @JsonKey(name: 'offset_seconds', defaultValue: 0)
  final int offsetSeconds;

  const PlaybackResponseModel({
    required this.found,
    this.message,
    this.segment,
    required this.offsetSeconds,
  });

  factory PlaybackResponseModel.fromJson(Map<String, dynamic> json) =>
      _$PlaybackResponseModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class PlaybackSegmentModel {
  final String id;
  @JsonKey(name: 'starts_at')
  final String startsAt;
  @JsonKey(name: 'ends_at')
  final String? endsAt;
  @JsonKey(name: 'duration_seconds', defaultValue: 0)
  final int durationSeconds;
  @JsonKey(name: 'stream_url')
  final String streamUrl;

  const PlaybackSegmentModel({
    required this.id,
    required this.startsAt,
    this.endsAt,
    required this.durationSeconds,
    required this.streamUrl,
  });

  factory PlaybackSegmentModel.fromJson(Map<String, dynamic> json) =>
      _$PlaybackSegmentModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class AvailableDatesResponseModel {
  final List<AvailableDateModel> dates;

  const AvailableDatesResponseModel({required this.dates});

  factory AvailableDatesResponseModel.fromJson(Map<String, dynamic> json) =>
      _$AvailableDatesResponseModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class AvailableDateModel {
  final String date;
  @JsonKey(name: 'segment_count', defaultValue: 0)
  final int segmentCount;
  @JsonKey(name: 'total_hours', defaultValue: 0.0)
  final double totalHours;

  const AvailableDateModel({
    required this.date,
    required this.segmentCount,
    required this.totalHours,
  });

  factory AvailableDateModel.fromJson(Map<String, dynamic> json) =>
      _$AvailableDateModelFromJson(json);
}
