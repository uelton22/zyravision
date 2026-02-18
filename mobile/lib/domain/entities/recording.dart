import 'package:freezed_annotation/freezed_annotation.dart';
import '../enums/recording_status.dart';

part 'recording.freezed.dart';

@freezed
class Recording with _$Recording {
  const factory Recording({
    required String id,
    required String cameraId,
    String? tenantId,
    String? streamId,
    @Default(RecordingStatus.unknown) RecordingStatus status,
    String? storagePath,
    @Default(0) int fileSizeBytes,
    String? fileSizeHuman,
    @Default(0) int durationSeconds,
    String? durationHuman,
    DateTime? startedAt,
    DateTime? endedAt,
    String? codec,
    String? resolution,
    int? fps,
    String? cameraName,
    String? playbackUrl,
    String? downloadUrl,
    DateTime? createdAt,
  }) = _Recording;

  const Recording._();

  bool get isCompleted => status.isCompleted;
  bool get isActive => status.isRecording;
  bool get hasPlayback => playbackUrl != null && isCompleted;
}
