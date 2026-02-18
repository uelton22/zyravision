import '../entities/timeline_data.dart';

abstract class RecordingRepository {
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

  /// Build the full stream URL for a segment.
  String getSegmentStreamUrl(String segmentId);

  /// Build the full download URL for a segment.
  String getSegmentDownloadUrl(String segmentId);
}
