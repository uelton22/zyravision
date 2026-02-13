import 'package:freezed_annotation/freezed_annotation.dart';

import '../enums/camera_status.dart';

part 'camera_summary.freezed.dart';

@freezed
class CameraSummary with _$CameraSummary {
  const factory CameraSummary({
    required String id,
    required String name,
    @Default(CameraStatus.unknown) CameraStatus status,
    @Default(false) bool isOnline,
    @Default(false) bool isRecording,
    String? snapshotUrl,
    String? hlsPath,
    String? manufacturer,
    String? location,
  }) = _CameraSummary;
}
