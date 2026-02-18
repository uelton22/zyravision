import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/config/app_config.dart';
import '../../core/config/constants.dart';
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
  String getSegmentStreamUrl(String segmentId) {
    return '${AppConfig.apiBaseUrl}${ApiPaths.segmentStream(segmentId)}';
  }

  @override
  String getSegmentDownloadUrl(String segmentId) {
    return '${AppConfig.apiBaseUrl}${ApiPaths.segmentDownload(segmentId)}';
  }
}
