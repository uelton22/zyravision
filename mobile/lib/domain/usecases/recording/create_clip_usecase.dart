import '../../repositories/recording_repository.dart';

class CreateClipUseCase {
  final RecordingRepository _repository;

  const CreateClipUseCase(this._repository);

  Future<void> call(
    String recordingId, {
    required double startSeconds,
    required double endSeconds,
  }) =>
      _repository.createClip(
        recordingId,
        startSeconds: startSeconds,
        endSeconds: endSeconds,
      );
}
