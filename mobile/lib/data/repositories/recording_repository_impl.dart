import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/config/app_config.dart';
import '../../core/config/constants.dart';
import '../../domain/entities/camera_recording_summary.dart';
import '../../domain/entities/paginated_result.dart';
import '../../domain/entities/recording.dart';
import '../../domain/entities/timeline_data.dart';
import '../../domain/repositories/recording_repository.dart';
import '../datasources/remote/recording_remote_datasource.dart';
import '../mappers/recording_mapper.dart';

final recordingRepositoryProvider = Provider<RecordingRepository>((ref) {
  return RecordingRepositoryImpl(ref.read(recordingRemoteDataSourceProvider));
});

class RecordingRepositoryImpl implements RecordingRepository {
  final RecordingRemoteDataSource _remote;

  RecordingRepositoryImpl(this._remote);

  @override
  Future<List<CameraRecordingSummary>> getCamerasWithRecordings() async {
    final response = await _remote.getCamerasWithRecordings();
    return RecordingMapper.toCameraRecordingSummaries(response);
  }

  @override
  Future<PaginatedResult<Recording>> getRecordings({
    int page = 1,
    int perPage = 15,
    String? cameraId,
    String? status,
    DateTime? from,
    DateTime? to,
  }) async {
    final response = await _remote.getRecordings(
      page: page,
      perPage: perPage,
      cameraId: cameraId,
      status: status,
      from: from?.toIso8601String(),
      to: to?.toIso8601String(),
    );
    return RecordingMapper.toPaginatedResult(response);
  }

  @override
  Future<PaginatedResult<Recording>> getCameraRecordings(
    String cameraId, {
    int page = 1,
    int perPage = 15,
    String? status,
    DateTime? from,
    DateTime? to,
  }) async {
    final response = await _remote.getCameraRecordings(
      cameraId,
      page: page,
      perPage: perPage,
      status: status,
      from: from?.toIso8601String(),
      to: to?.toIso8601String(),
    );
    return RecordingMapper.toPaginatedResult(response);
  }

  @override
  Future<Recording> getRecordingById(String id) async {
    final model = await _remote.getRecordingById(id);
    return RecordingMapper.toEntity(model);
  }

  @override
  Future<void> deleteRecording(String id) => _remote.deleteRecording(id);

  @override
  Future<TimelineData> getTimeline(
    String cameraId, {
    DateTime? start,
    DateTime? end,
  }) async {
    final response = await _remote.getTimeline(
      cameraId,
      start: start?.toIso8601String(),
      end: end?.toIso8601String(),
    );
    return RecordingMapper.toTimelineData(response);
  }

  @override
  Future<List<AvailableDate>> getAvailableDates(String cameraId) async {
    final response = await _remote.getAvailableDates(cameraId);
    return RecordingMapper.toAvailableDates(response);
  }

  @override
  Future<PlaybackSegmentInfo> getPlaybackAtTime(
    String cameraId,
    DateTime time,
  ) async {
    final response = await _remote.getPlaybackAtTime(
      cameraId,
      time.toIso8601String(),
    );
    return RecordingMapper.toPlaybackInfo(response);
  }

  @override
  Future<void> createClip(
    String recordingId, {
    required double startSeconds,
    required double endSeconds,
  }) =>
      _remote.createClip(
        recordingId,
        start: startSeconds,
        end: endSeconds,
      );

  @override
  String getSegmentStreamUrl(String segmentId) {
    return '${AppConfig.apiBaseUrl}${ApiPaths.segmentStream(segmentId)}';
  }

  @override
  String getSegmentDownloadUrl(String segmentId) {
    return '${AppConfig.apiBaseUrl}${ApiPaths.segmentDownload(segmentId)}';
  }
}
