import 'package:freezed_annotation/freezed_annotation.dart';
import '../enums/segment_status.dart';

part 'recording_segment.freezed.dart';

@freezed
class RecordingSegment with _$RecordingSegment {
  const factory RecordingSegment({
    required String id,
    required String cameraId,
    String? tenantId,
    @Default(SegmentStatus.unknown) SegmentStatus status,
    String? filename,
    @Default(0) int fileSizeBytes,
    @Default(0) int durationSeconds,
    DateTime? startsAt,
    DateTime? endsAt,
    String? codec,
    String? resolution,
    int? fps,
    String? thumbnailPath,
    @Default(false) bool hasLocal,
    @Default(false) bool hasRemote,
  }) = _RecordingSegment;

  const RecordingSegment._();

  bool get isAvailable => status.isAvailable;
}
