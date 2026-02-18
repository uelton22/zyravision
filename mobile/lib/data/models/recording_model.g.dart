// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recording_model.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecordingListResponseModel _$RecordingListResponseModelFromJson(
  Map<String, dynamic> json,
) => RecordingListResponseModel(
  data:
      (json['data'] as List<dynamic>)
          .map((e) => RecordingModel.fromJson(e as Map<String, dynamic>))
          .toList(),
  meta: RecordingPaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
);

RecordingPaginationMeta _$RecordingPaginationMetaFromJson(
  Map<String, dynamic> json,
) => RecordingPaginationMeta(
  currentPage: (json['current_page'] as num).toInt(),
  perPage: (json['per_page'] as num).toInt(),
  total: (json['total'] as num).toInt(),
  lastPage: (json['last_page'] as num).toInt(),
);

RecordingModel _$RecordingModelFromJson(Map<String, dynamic> json) =>
    RecordingModel(
      id: json['id'] as String,
      cameraId: json['camera_id'] as String,
      tenantId: json['tenant_id'] as String?,
      streamId: json['stream_id'] as String?,
      status: json['status'] as String? ?? 'unknown',
      storagePath: json['storage_path'] as String?,
      fileSizeBytes: (json['file_size_bytes'] as num?)?.toInt() ?? 0,
      fileSizeHuman: json['file_size_human'] as String?,
      durationSeconds: (json['duration_seconds'] as num?)?.toInt() ?? 0,
      durationHuman: json['duration_human'] as String?,
      startedAt: json['started_at'] as String?,
      endedAt: json['ended_at'] as String?,
      codec: json['codec'] as String?,
      resolution: json['resolution'] as String?,
      fps: (json['fps'] as num?)?.toInt(),
      playbackUrl: json['playback_url'] as String?,
      downloadUrl: json['download_url'] as String?,
      createdAt: json['created_at'] as String?,
      camera:
          json['camera'] == null
              ? null
              : RecordingCameraModel.fromJson(
                json['camera'] as Map<String, dynamic>,
              ),
    );

RecordingCameraModel _$RecordingCameraModelFromJson(
  Map<String, dynamic> json,
) => RecordingCameraModel(
  id: json['id'] as String,
  name: json['name'] as String,
  slug: json['slug'] as String?,
);
