import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/constants.dart';
import '../../../core/network/api_client.dart';
import '../../models/timeline_model.dart';

final recordingRemoteDataSourceProvider =
    Provider<RecordingRemoteDataSource>((ref) {
  return RecordingRemoteDataSource(ref.read(apiClientProvider));
});

class RecordingRemoteDataSource {
  final ApiClient _client;

  RecordingRemoteDataSource(this._client);

  Future<TimelineResponseModel> getTimeline(
    String cameraId, {
    String? start,
    String? end,
  }) async {
    final queryParams = <String, dynamic>{};
    if (start != null) queryParams['start'] = start;
    if (end != null) queryParams['end'] = end;

    final response = await _client.dio.get(
      ApiPaths.cameraTimeline(cameraId),
      queryParameters: queryParams,
    );
    final json = response.data as Map<String, dynamic>;
    debugPrint(
      '[RecordingDataSource] timeline response: '
      'total_segments=${json['total_segments']}, '
      'segments=${(json['segments'] as List?)?.length}, '
      'ranges=${(json['ranges'] as List?)?.length}',
    );
    return TimelineResponseModel.fromJson(json);
  }

  Future<AvailableDatesResponseModel> getAvailableDates(
    String cameraId,
  ) async {
    final response = await _client.dio.get(
      ApiPaths.cameraTimelineDates(cameraId),
    );
    return AvailableDatesResponseModel.fromJson(
      response.data as Map<String, dynamic>,
    );
  }

  Future<PlaybackResponseModel> getPlaybackAtTime(
    String cameraId,
    String time,
  ) async {
    final response = await _client.dio.get(
      ApiPaths.cameraPlayback(cameraId),
      queryParameters: {'time': time},
    );
    return PlaybackResponseModel.fromJson(
      response.data as Map<String, dynamic>,
    );
  }
}
