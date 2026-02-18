import 'package:flutter/foundation.dart';

import '../../domain/entities/camera.dart';
import '../../domain/entities/paginated_result.dart';
import '../../domain/entities/stream_info.dart';
import '../../domain/enums/camera_status.dart';
import '../models/camera_model.dart';

class CameraMapper {
  CameraMapper._();

  static Camera toEntity(CameraModel model) {
    debugPrint('[CameraMapper] ${model.name}: '
        'snapshot_url=${model.snapshotUrl}, '
        'activeStream=${model.activeStream != null}, '
        'streamStatus=${model.activeStream?.status}, '
        'hlsUrl=${model.activeStream?.urls?.hls}');
    return Camera(
      id: model.id,
      name: model.name,
      slug: model.slug,
      manufacturer: model.manufacturer,
      model: model.model,
      ipAddress: model.ipAddress,
      status: CameraStatus.fromString(model.status),
      isOnline: model.isOnline,
      isRecording: model.isRecording,
      codec: model.codec,
      resolution: model.resolution,
      fps: model.fps,
      snapshotUrl: model.snapshotUrl,
      recordingQuality: model.recordingQuality,
      stream: model.activeStream != null
          ? _mapStream(model.activeStream!)
          : null,
      groupId: model.group?.id,
      groupName: model.group?.name,
      location: model.location,
      createdAt: model.createdAt != null
          ? DateTime.tryParse(model.createdAt!)
          : null,
    );
  }

  static StreamInfo _mapStream(CameraStreamModel model) {
    return StreamInfo(
      id: model.id,
      cameraId: model.cameraId,
      status: model.status,
      mediamtxPath: model.mediamtxPath,
      startedAt: model.startedAt,
      errorMessage: model.errorMessage,
      urls: model.urls != null
          ? StreamUrls(
              hls: model.urls!.hls,
              webrtc: model.urls!.webrtc,
              rtsp: model.urls!.rtsp,
            )
          : null,
    );
  }

  static PaginatedResult<Camera> toPaginatedResult(
    CameraListResponseModel response,
  ) {
    return PaginatedResult(
      data: response.data.map(toEntity).toList(),
      currentPage: response.meta.currentPage,
      lastPage: response.meta.lastPage,
      perPage: response.meta.perPage,
      total: response.meta.total,
    );
  }
}
