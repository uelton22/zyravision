import '../../entities/timeline_data.dart';
import '../../repositories/recording_repository.dart';

class GetAvailableDatesUseCase {
  final RecordingRepository _repository;

  const GetAvailableDatesUseCase(this._repository);

  Future<List<AvailableDate>> call(String cameraId) =>
      _repository.getAvailableDates(cameraId);
}
