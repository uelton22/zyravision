// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'camera_model.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CameraListResponseModel _$CameraListResponseModelFromJson(
  Map<String, dynamic> json,
) => CameraListResponseModel(
  data:
      (json['data'] as List<dynamic>)
          .map((e) => CameraModel.fromJson(e as Map<String, dynamic>))
          .toList(),
  meta: PaginationMetaModel.fromJson(json['meta'] as Map<String, dynamic>),
);

CameraDetailResponseModel _$CameraDetailResponseModelFromJson(
  Map<String, dynamic> json,
) => CameraDetailResponseModel(
  data: CameraModel.fromJson(json['data'] as Map<String, dynamic>),
);

CameraModel _$CameraModelFromJson(Map<String, dynamic> json) => CameraModel(
  id: json['id'] as String,
  name: json['name'] as String,
  slug: json['slug'] as String?,
  manufacturer: json['manufacturer'] as String?,
  model: json['model'] as String?,
  ipAddress: json['ip_address'] as String?,
  status: json['status'] as String? ?? 'offline',
  isOnline: json['is_online'] as bool? ?? false,
  isRecording: json['is_recording'] as bool? ?? false,
  codec: json['codec'] as String?,
  resolution: json['resolution'] as String?,
  fps: (json['fps'] as num?)?.toInt(),
  snapshotUrl: json['snapshot_url'] as String?,
  recordingQuality: json['recording_quality'] as String?,
  activeStream:
      json['active_stream'] == null
          ? null
          : CameraStreamModel.fromJson(
            json['active_stream'] as Map<String, dynamic>,
          ),
  group:
      json['group'] == null
          ? null
          : CameraGroupModel.fromJson(json['group'] as Map<String, dynamic>),
  location: json['location'] as String?,
  createdAt: json['created_at'] as String?,
);

CameraStreamModel _$CameraStreamModelFromJson(Map<String, dynamic> json) =>
    CameraStreamModel(
      id: json['id'] as String,
      cameraId: json['camera_id'] as String?,
      status: json['status'] as String? ?? 'inactive',
      mediamtxPath: json['mediamtx_path'] as String?,
      startedAt: json['started_at'] as String?,
      errorMessage: json['error_message'] as String?,
      urls:
          json['urls'] == null
              ? null
              : StreamUrlsModel.fromJson(json['urls'] as Map<String, dynamic>),
    );

StreamUrlsModel _$StreamUrlsModelFromJson(Map<String, dynamic> json) =>
    StreamUrlsModel(
      hls: json['hls'] as String?,
      webrtc: json['webrtc'] as String?,
      rtsp: json['rtsp'] as String?,
    );

CameraGroupModel _$CameraGroupModelFromJson(Map<String, dynamic> json) =>
    CameraGroupModel(id: json['id'] as String, name: json['name'] as String);

PaginationMetaModel _$PaginationMetaModelFromJson(Map<String, dynamic> json) =>
    PaginationMetaModel(
      currentPage: (json['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (json['last_page'] as num?)?.toInt() ?? 1,
      perPage: (json['per_page'] as num?)?.toInt() ?? 15,
      total: (json['total'] as num?)?.toInt() ?? 0,
    );
