import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/camera.dart';
import '../../domain/entities/paginated_result.dart';
import '../../domain/repositories/camera_repository.dart';
import '../datasources/remote/camera_remote_datasource.dart';
import '../mappers/camera_mapper.dart';

final cameraRepositoryProvider = Provider<CameraRepository>((ref) {
  return CameraRepositoryImpl(ref.read(cameraRemoteDataSourceProvider));
});

class CameraRepositoryImpl implements CameraRepository {
  final CameraRemoteDataSource _remote;

  CameraRepositoryImpl(this._remote);

  @override
  Future<PaginatedResult<Camera>> getCameras({
    int page = 1,
    int perPage = 15,
    String? search,
    String? status,
    bool? isRecording,
    String? groupId,
    String sort = 'name',
    String direction = 'asc',
  }) async {
    final response = await _remote.getCameras(
      page: page,
      perPage: perPage,
      search: search,
      status: status,
      isRecording: isRecording,
      groupId: groupId,
      sort: sort,
      direction: direction,
    );
    return CameraMapper.toPaginatedResult(response);
  }

  @override
  Future<Camera> getCameraById(String id) async {
    final model = await _remote.getCameraById(id);
    return CameraMapper.toEntity(model);
  }

  @override
  Future<void> startStream(String cameraId) => _remote.startStream(cameraId);

  @override
  Future<void> stopStream(String cameraId) => _remote.stopStream(cameraId);
}
