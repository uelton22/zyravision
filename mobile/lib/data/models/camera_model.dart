import 'package:json_annotation/json_annotation.dart';

part 'camera_model.g.dart';

@JsonSerializable(createToJson: false)
class CameraListResponseModel {
  final List<CameraModel> data;
  final PaginationMetaModel meta;

  const CameraListResponseModel({
    required this.data,
    required this.meta,
  });

  factory CameraListResponseModel.fromJson(Map<String, dynamic> json) =>
      _$CameraListResponseModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class CameraDetailResponseModel {
  final CameraModel data;

  const CameraDetailResponseModel({required this.data});

  factory CameraDetailResponseModel.fromJson(Map<String, dynamic> json) =>
      _$CameraDetailResponseModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class CameraModel {
  final String id;
  final String name;
  final String? slug;
  final String? manufacturer;
  final String? model;
  @JsonKey(name: 'ip_address')
  final String? ipAddress;
  @JsonKey(defaultValue: 'offline')
  final String status;
  @JsonKey(name: 'is_online', defaultValue: false)
  final bool isOnline;
  @JsonKey(name: 'is_recording', defaultValue: false)
  final bool isRecording;
  final String? codec;
  final String? resolution;
  final int? fps;
  @JsonKey(name: 'snapshot_url')
  final String? snapshotUrl;
  @JsonKey(name: 'recording_quality')
  final String? recordingQuality;
  @JsonKey(name: 'active_stream')
  final CameraStreamModel? activeStream;
  final CameraGroupModel? group;
  final String? location;
  @JsonKey(name: 'created_at')
  final String? createdAt;

  const CameraModel({
    required this.id,
    required this.name,
    this.slug,
    this.manufacturer,
    this.model,
    this.ipAddress,
    this.status = 'offline',
    this.isOnline = false,
    this.isRecording = false,
    this.codec,
    this.resolution,
    this.fps,
    this.snapshotUrl,
    this.recordingQuality,
    this.activeStream,
    this.group,
    this.location,
    this.createdAt,
  });

  factory CameraModel.fromJson(Map<String, dynamic> json) =>
      _$CameraModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class CameraStreamModel {
  final String id;
  @JsonKey(name: 'camera_id')
  final String? cameraId;
  @JsonKey(defaultValue: 'inactive')
  final String status;
  @JsonKey(name: 'mediamtx_path')
  final String? mediamtxPath;
  @JsonKey(name: 'started_at')
  final String? startedAt;
  @JsonKey(name: 'error_message')
  final String? errorMessage;
  final StreamUrlsModel? urls;

  const CameraStreamModel({
    required this.id,
    this.cameraId,
    this.status = 'inactive',
    this.mediamtxPath,
    this.startedAt,
    this.errorMessage,
    this.urls,
  });

  factory CameraStreamModel.fromJson(Map<String, dynamic> json) =>
      _$CameraStreamModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class StreamUrlsModel {
  final String? hls;
  final String? webrtc;
  final String? rtsp;

  const StreamUrlsModel({this.hls, this.webrtc, this.rtsp});

  factory StreamUrlsModel.fromJson(Map<String, dynamic> json) =>
      _$StreamUrlsModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class CameraGroupModel {
  final String id;
  final String name;

  const CameraGroupModel({required this.id, required this.name});

  factory CameraGroupModel.fromJson(Map<String, dynamic> json) =>
      _$CameraGroupModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class PaginationMetaModel {
  @JsonKey(name: 'current_page', defaultValue: 1)
  final int currentPage;
  @JsonKey(name: 'last_page', defaultValue: 1)
  final int lastPage;
  @JsonKey(name: 'per_page', defaultValue: 15)
  final int perPage;
  @JsonKey(defaultValue: 0)
  final int total;

  const PaginationMetaModel({
    this.currentPage = 1,
    this.lastPage = 1,
    this.perPage = 15,
    this.total = 0,
  });

  factory PaginationMetaModel.fromJson(Map<String, dynamic> json) =>
      _$PaginationMetaModelFromJson(json);
}
