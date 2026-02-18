import '../../entities/camera.dart';
import '../../repositories/camera_repository.dart';

class GetCameraDetailUseCase {
  final CameraRepository _repository;

  const GetCameraDetailUseCase(this._repository);

  Future<Camera> call(String id) => _repository.getCameraById(id);
}
