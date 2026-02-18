import '../../domain/entities/camera_recording_summary.dart';
import '../../domain/entities/paginated_result.dart';
import '../../domain/entities/recording.dart';
import '../../domain/entities/recording_segment.dart';
import '../../domain/entities/timeline_data.dart';
import '../../domain/enums/recording_status.dart';
import '../../domain/enums/segment_status.dart';
import '../models/camera_recording_summary_model.dart';
import '../models/recording_model.dart';
import '../models/timeline_model.dart';

class RecordingMapper {
  RecordingMapper._();

  static Recording toEntity(RecordingModel model) {
    return Recording(
      id: model.id,
      cameraId: model.cameraId,
      tenantId: model.tenantId,
      streamId: model.streamId,
      status: RecordingStatus.fromString(model.status),
      storagePath: model.storagePath,
      fileSizeBytes: model.fileSizeBytes,
      fileSizeHuman: model.fileSizeHuman,
      durationSeconds: model.durationSeconds,
      durationHuman: model.durationHuman,
      startedAt:
          model.startedAt != null ? DateTime.tryParse(model.startedAt!) : null,
      endedAt:
          model.endedAt != null ? DateTime.tryParse(model.endedAt!) : null,
      codec: model.codec,
      resolution: model.resolution,
      fps: model.fps,
      cameraName: model.camera?.name,
      playbackUrl: model.playbackUrl,
      downloadUrl: model.downloadUrl,
      createdAt:
          model.createdAt != null ? DateTime.tryParse(model.createdAt!) : null,
    );
  }

  static PaginatedResult<Recording> toPaginatedResult(
    RecordingListResponseModel response,
  ) {
    return PaginatedResult(
      data: response.data.map(toEntity).toList(),
      currentPage: response.meta.currentPage,
      lastPage: response.meta.lastPage,
      perPage: response.meta.perPage,
      total: response.meta.total,
    );
  }

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

  static List<CameraRecordingSummary> toCameraRecordingSummaries(
    CameraRecordingSummaryListResponse response,
  ) {
    return response.data.map(_toCameraRecordingSummary).toList();
  }

  static CameraRecordingSummary _toCameraRecordingSummary(
    CameraRecordingSummaryModel model,
  ) {
    return CameraRecordingSummary(
      cameraId: model.cameraId,
      cameraName: model.cameraName,
      cameraSlug: model.cameraSlug,
      cameraStatus: model.cameraStatus,
      isOnline: model.isOnline,
      isRecording: model.isRecording,
      totalSegments: model.totalSegments,
      totalDurationSeconds: model.totalDurationSeconds,
      totalDurationHuman: model.totalDurationHuman,
      totalHours: model.totalHours,
      totalSizeBytes: model.totalSizeBytes,
      totalSizeHuman: model.totalSizeHuman,
      totalDays: model.totalDays,
      firstRecordingAt: model.firstRecordingAt != null
          ? DateTime.tryParse(model.firstRecordingAt!)
          : null,
      lastRecordingAt: model.lastRecordingAt != null
          ? DateTime.tryParse(model.lastRecordingAt!)
          : null,
    );
  }
}
