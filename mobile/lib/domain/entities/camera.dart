import 'package:freezed_annotation/freezed_annotation.dart';

import '../enums/camera_status.dart';
import 'stream_info.dart';

part 'camera.freezed.dart';

@freezed
class Camera with _$Camera {
  const factory Camera({
    required String id,
    required String name,
    String? slug,
    String? manufacturer,
    String? model,
    String? ipAddress,
    @Default(CameraStatus.unknown) CameraStatus status,
    @Default(false) bool isOnline,
    @Default(false) bool isRecording,
    String? codec,
    String? resolution,
    int? fps,
    String? snapshotUrl,
    String? recordingQuality,
    StreamInfo? stream,
    String? groupId,
    String? groupName,
    String? location,
    DateTime? createdAt,
  }) = _Camera;
}
