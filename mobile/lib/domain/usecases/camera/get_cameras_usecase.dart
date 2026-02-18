import '../../entities/camera.dart';
import '../../entities/paginated_result.dart';
import '../../repositories/camera_repository.dart';

class GetCamerasUseCase {
  final CameraRepository _repository;

  const GetCamerasUseCase(this._repository);

  Future<PaginatedResult<Camera>> call({
    int page = 1,
    int perPage = 15,
    String? search,
    String? status,
    bool? isRecording,
    String? groupId,
    String sort = 'name',
    String direction = 'asc',
  }) =>
      _repository.getCameras(
        page: page,
        perPage: perPage,
        search: search,
        status: status,
        isRecording: isRecording,
        groupId: groupId,
        sort: sort,
        direction: direction,
      );
}
