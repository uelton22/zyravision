import 'package:freezed_annotation/freezed_annotation.dart';
import '../../domain/entities/camera_recording_summary.dart';

part 'recording_list_state.freezed.dart';

@freezed
class RecordingListState with _$RecordingListState {
  const factory RecordingListState({
    @Default([]) List<CameraRecordingSummary> cameras,
    @Default(false) bool isLoading,
    String? errorMessage,
  }) = _RecordingListState;

  const RecordingListState._();

  bool get hasError => errorMessage != null;
  bool get isEmpty => cameras.isEmpty && !isLoading;
  int get totalCameras => cameras.length;

  int get totalHours =>
      cameras.fold(0, (sum, c) => sum + (c.totalHours.round()));

  int get totalDays =>
      cameras.fold(0, (sum, c) => sum + c.totalDays);
}
