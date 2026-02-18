import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/constants.dart';
import '../../../core/network/api_client.dart';
import '../../models/camera_model.dart';

final cameraRemoteDataSourceProvider =
    Provider<CameraRemoteDataSource>((ref) {
  return CameraRemoteDataSource(ref.read(apiClientProvider));
});

class CameraRemoteDataSource {
  final ApiClient _client;

  CameraRemoteDataSource(this._client);

  Future<CameraListResponseModel> getCameras({
    int page = 1,
    int perPage = 15,
    String? search,
    String? status,
    bool? isRecording,
    String? groupId,
    String sort = 'name',
    String direction = 'asc',
  }) async {
    final queryParams = <String, dynamic>{
      'page': page,
      'per_page': perPage,
      'sort': sort,
      'direction': direction,
      'include': 'activeStream',
    };

    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (status != null) queryParams['status'] = status;
    if (isRecording != null) queryParams['is_recording'] = isRecording;
    if (groupId != null) queryParams['group_id'] = groupId;

    final response = await _client.dio.get(
      ApiPaths.cameras,
      queryParameters: queryParams,
    );
    return CameraListResponseModel.fromJson(
      response.data as Map<String, dynamic>,
    );
  }

  Future<CameraModel> getCameraById(String id) async {
    final response = await _client.dio.get(
      ApiPaths.cameraDetail(id),
      queryParameters: {'include': 'activeStream'},
    );
    final json = response.data as Map<String, dynamic>;
    if (json.containsKey('data') && json['data'] is Map<String, dynamic>) {
      return CameraModel.fromJson(json['data'] as Map<String, dynamic>);
    }
    return CameraModel.fromJson(json);
  }

  Future<void> startStream(String cameraId) async {
    await _client.dio.post(ApiPaths.cameraStreamStart(cameraId));
  }

  Future<void> stopStream(String cameraId) async {
    await _client.dio.post(ApiPaths.cameraStreamStop(cameraId));
  }
}
