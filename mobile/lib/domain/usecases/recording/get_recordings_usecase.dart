import '../../entities/paginated_result.dart';
import '../../entities/recording.dart';
import '../../repositories/recording_repository.dart';

class GetRecordingsUseCase {
  final RecordingRepository _repository;

  const GetRecordingsUseCase(this._repository);

  Future<PaginatedResult<Recording>> call({
    int page = 1,
    int perPage = 15,
    String? cameraId,
    String? status,
    DateTime? from,
    DateTime? to,
  }) =>
      _repository.getRecordings(
        page: page,
        perPage: perPage,
        cameraId: cameraId,
        status: status,
        from: from,
        to: to,
      );
}
