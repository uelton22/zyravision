import '../../domain/entities/recording_segment.dart';
import '../../domain/entities/timeline_data.dart';
import '../../domain/enums/segment_status.dart';
import '../models/timeline_model.dart';

class RecordingMapper {
  RecordingMapper._();

  static TimelineData toTimelineData(TimelineResponseModel model) {
    return TimelineData(
      cameraId: model.cameraId,
      rangeStart: DateTime.parse(model.range.start),
      rangeEnd: DateTime.parse(model.range.end),
      segments: model.segments.map(_toSegmentEntity).toList(),
      ranges: model.ranges.map(_toTimelineRange).toList(),
      totalSegments: model.totalSegments,
      totalDurationSeconds: model.totalDurationSeconds,
    );
  }

  static RecordingSegment _toSegmentEntity(TimelineSegmentModel model) {
    return RecordingSegment(
      id: model.id,
      cameraId: '',
      status: SegmentStatus.fromString(model.status),
      durationSeconds: model.durationSeconds,
      fileSizeBytes: model.fileSizeBytes,
      startsAt: DateTime.tryParse(model.startsAt),
      endsAt: model.endsAt != null ? DateTime.tryParse(model.endsAt!) : null,
      hasLocal: model.hasLocal,
      hasRemote: model.hasRemote,
      thumbnailUrl: model.thumbnailUrl,
    );
  }

  static TimelineRange _toTimelineRange(TimelineRangeModel model) {
    return TimelineRange(
      start: DateTime.parse(model.start),
      end: DateTime.parse(model.end),
    );
  }

  static PlaybackSegmentInfo toPlaybackInfo(PlaybackResponseModel model) {
    final seg = model.segment!;
    return PlaybackSegmentInfo(
      segmentId: seg.id,
      startsAt: DateTime.parse(seg.startsAt),
      endsAt: seg.endsAt != null ? DateTime.tryParse(seg.endsAt!) : null,
      durationSeconds: seg.durationSeconds,
      streamUrl: seg.streamUrl,
      offsetSeconds: model.offsetSeconds,
    );
  }

  static List<AvailableDate> toAvailableDates(
    AvailableDatesResponseModel model,
  ) {
    return model.dates
        .map((d) => AvailableDate(
              date: DateTime.parse(d.date),
              segmentCount: d.segmentCount,
              totalHours: d.totalHours,
            ))
        .toList();
  }
}
