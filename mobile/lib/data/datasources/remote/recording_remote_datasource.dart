import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/constants.dart';
import '../../../core/network/api_client.dart';
import '../../models/camera_recording_summary_model.dart';
import '../../models/recording_model.dart';
import '../../models/timeline_model.dart';

final recordingRemoteDataSourceProvider =
    Provider<RecordingRemoteDataSource>((ref) {
  return RecordingRemoteDataSource(ref.read(apiClientProvider));
});

class RecordingRemoteDataSource {
  final ApiClient _client;

  RecordingRemoteDataSource(this._client);

  Future<CameraRecordingSummaryListResponse> getCamerasWithRecordings() async {
    final response = await _client.dio.get(
      ApiPaths.camerasRecordingSummary,
    );
    return CameraRecordingSummaryListResponse.fromJson(
      response.data as Map<String, dynamic>,
    );
  }

  Future<RecordingListResponseModel> getRecordings({
    int page = 1,
    int perPage = 15,
    String? cameraId,
    String? status,
    String? from,
    String? to,
  }) async {
    final queryParams = <String, dynamic>{
      'page': page,
      'per_page': perPage,
      'include': 'camera',
    };

    if (cameraId != null) queryParams['camera_id'] = cameraId;
    if (status != null) queryParams['status'] = status;
    if (from != null) queryParams['from'] = from;
    if (to != null) queryParams['to'] = to;

    final response = await _client.dio.get(
      ApiPaths.recordings,
      queryParameters: queryParams,
    );
    return RecordingListResponseModel.fromJson(
      response.data as Map<String, dynamic>,
    );
  }

  Future<RecordingListResponseModel> getCameraRecordings(
    String cameraId, {
    int page = 1,
    int perPage = 15,
    String? status,
    String? from,
    String? to,
  }) async {
    final queryParams = <String, dynamic>{
      'page': page,
      'per_page': perPage,
    };

    if (status != null) queryParams['status'] = status;
    if (from != null) queryParams['from'] = from;
    if (to != null) queryParams['to'] = to;

    final response = await _client.dio.get(
      ApiPaths.cameraRecordings(cameraId),
      queryParameters: queryParams,
    );
    return RecordingListResponseModel.fromJson(
      response.data as Map<String, dynamic>,
    );
  }

  Future<RecordingModel> getRecordingById(String id) async {
    final response = await _client.dio.get(ApiPaths.recordingDetail(id));
    final json = response.data as Map<String, dynamic>;
    if (json.containsKey('data') && json['data'] is Map<String, dynamic>) {
      return RecordingModel.fromJson(json['data'] as Map<String, dynamic>);
    }
    return RecordingModel.fromJson(json);
  }

  Future<void> deleteRecording(String id) async {
    await _client.dio.delete(ApiPaths.recordingDetail(id));
  }

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
    return TimelineResponseModel.fromJson(
      response.data as Map<String, dynamic>,
    );
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

  Future<void> createClip(
    String recordingId, {
    required double start,
    required double end,
  }) async {
    await _client.dio.post(
      ApiPaths.recordingClip(recordingId),
      data: {'start': start, 'end': end},
    );
  }
}
