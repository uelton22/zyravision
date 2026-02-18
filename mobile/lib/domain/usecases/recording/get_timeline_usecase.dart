import '../../entities/timeline_data.dart';
import '../../repositories/recording_repository.dart';

class GetTimelineUseCase {
  final RecordingRepository _repository;

  const GetTimelineUseCase(this._repository);

  Future<TimelineData> call(
    String cameraId, {
    DateTime? start,
    DateTime? end,
  }) =>
      _repository.getTimeline(cameraId, start: start, end: end);
}
