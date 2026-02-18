import '../entities/camera.dart';
import '../entities/paginated_result.dart';

abstract class CameraRepository {
  Future<PaginatedResult<Camera>> getCameras({
    int page = 1,
    int perPage = 15,
    String? search,
    String? status,
    bool? isRecording,
    String? groupId,
    String sort = 'name',
    String direction = 'asc',
  });

  Future<Camera> getCameraById(String id);

  Future<void> startStream(String cameraId);

  Future<void> stopStream(String cameraId);
}
