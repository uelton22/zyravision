import '../../repositories/camera_repository.dart';

class ToggleStreamUseCase {
  final CameraRepository _repository;

  const ToggleStreamUseCase(this._repository);

  Future<void> start(String cameraId) => _repository.startStream(cameraId);

  Future<void> stop(String cameraId) => _repository.stopStream(cameraId);
}
