import 'package:freezed_annotation/freezed_annotation.dart';
import 'recording_segment.dart';

part 'timeline_data.freezed.dart';

@freezed
class TimelineData with _$TimelineData {
  const factory TimelineData({
    required String cameraId,
    required DateTime rangeStart,
    required DateTime rangeEnd,
    @Default([]) List<RecordingSegment> segments,
    @Default([]) List<TimelineRange> ranges,
    @Default(0) int totalSegments,
    @Default(0) int totalDurationSeconds,
  }) = _TimelineData;

  const TimelineData._();

  bool get hasRecordings => segments.isNotEmpty;

  double get coveragePercent {
    final totalRange = rangeEnd.difference(rangeStart).inSeconds;
    if (totalRange <= 0) return 0;
    return (totalDurationSeconds / totalRange * 100).clamp(0, 100);
  }
}

@freezed
class TimelineRange with _$TimelineRange {
  const factory TimelineRange({
    required DateTime start,
    required DateTime end,
  }) = _TimelineRange;

  const TimelineRange._();

  Duration get duration => end.difference(start);
}

@freezed
class PlaybackSegmentInfo with _$PlaybackSegmentInfo {
  const factory PlaybackSegmentInfo({
    required String segmentId,
    required DateTime startsAt,
    DateTime? endsAt,
    @Default(0) int durationSeconds,
    required String streamUrl,
    @Default(0) int offsetSeconds,
  }) = _PlaybackSegmentInfo;
}

@freezed
class AvailableDate with _$AvailableDate {
  const factory AvailableDate({
    required DateTime date,
    @Default(0) int segmentCount,
    @Default(0.0) double totalHours,
  }) = _AvailableDate;
}
