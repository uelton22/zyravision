import '../../entities/timeline_data.dart';
import '../../repositories/recording_repository.dart';

class GetPlaybackSegmentUseCase {
  final RecordingRepository _repository;

  const GetPlaybackSegmentUseCase(this._repository);

  Future<PlaybackSegmentInfo> call(String cameraId, DateTime time) =>
      _repository.getPlaybackAtTime(cameraId, time);
}
