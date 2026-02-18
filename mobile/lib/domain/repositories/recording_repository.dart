import '../entities/camera_recording_summary.dart';
import '../entities/paginated_result.dart';
import '../entities/recording.dart';
import '../entities/timeline_data.dart';

abstract class RecordingRepository {
  /// Get cameras that have recordings with summary stats.
  Future<List<CameraRecordingSummary>> getCamerasWithRecordings();

  /// Get paginated list of recordings with optional filters.
  Future<PaginatedResult<Recording>> getRecordings({
    int page = 1,
    int perPage = 15,
    String? cameraId,
    String? status,
    DateTime? from,
    DateTime? to,
  });

  /// Get recordings for a specific camera.
  Future<PaginatedResult<Recording>> getCameraRecordings(
    String cameraId, {
    int page = 1,
    int perPage = 15,
    String? status,
    DateTime? from,
    DateTime? to,
  });

  /// Get recording detail by id.
  Future<Recording> getRecordingById(String id);

  /// Delete a recording.
  Future<void> deleteRecording(String id);

  /// Get timeline data (segments) for a camera within a time range.
  Future<TimelineData> getTimeline(
    String cameraId, {
    DateTime? start,
    DateTime? end,
  });

  /// Get dates that have available recordings for a camera.
  Future<List<AvailableDate>> getAvailableDates(String cameraId);

  /// Find the segment that contains a specific time for playback.
  Future<PlaybackSegmentInfo> getPlaybackAtTime(
    String cameraId,
    DateTime time,
  );

  /// Create a clip from a recording.
  Future<void> createClip(
    String recordingId, {
    required double startSeconds,
    required double endSeconds,
  });

  /// Build the full stream URL for a segment.
  String getSegmentStreamUrl(String segmentId);

  /// Build the full download URL for a segment.
  String getSegmentDownloadUrl(String segmentId);
}
