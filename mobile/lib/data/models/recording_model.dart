import 'package:json_annotation/json_annotation.dart';

part 'recording_model.g.dart';

@JsonSerializable(createToJson: false)
class RecordingListResponseModel {
  final List<RecordingModel> data;
  final RecordingPaginationMeta meta;

  const RecordingListResponseModel({
    required this.data,
    required this.meta,
  });

  factory RecordingListResponseModel.fromJson(Map<String, dynamic> json) =>
      _$RecordingListResponseModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class RecordingPaginationMeta {
  @JsonKey(name: 'current_page')
  final int currentPage;
  @JsonKey(name: 'per_page')
  final int perPage;
  final int total;
  @JsonKey(name: 'last_page')
  final int lastPage;

  const RecordingPaginationMeta({
    required this.currentPage,
    required this.perPage,
    required this.total,
    required this.lastPage,
  });

  factory RecordingPaginationMeta.fromJson(Map<String, dynamic> json) =>
      _$RecordingPaginationMetaFromJson(json);
}

@JsonSerializable(createToJson: false)
class RecordingModel {
  final String id;
  @JsonKey(name: 'camera_id')
  final String cameraId;
  @JsonKey(name: 'tenant_id')
  final String? tenantId;
  @JsonKey(name: 'stream_id')
  final String? streamId;
  @JsonKey(defaultValue: 'unknown')
  final String status;
  @JsonKey(name: 'storage_path')
  final String? storagePath;
  @JsonKey(name: 'file_size_bytes', defaultValue: 0)
  final int fileSizeBytes;
  @JsonKey(name: 'file_size_human')
  final String? fileSizeHuman;
  @JsonKey(name: 'duration_seconds', defaultValue: 0)
  final int durationSeconds;
  @JsonKey(name: 'duration_human')
  final String? durationHuman;
  @JsonKey(name: 'started_at')
  final String? startedAt;
  @JsonKey(name: 'ended_at')
  final String? endedAt;
  final String? codec;
  final String? resolution;
  final int? fps;
  @JsonKey(name: 'playback_url')
  final String? playbackUrl;
  @JsonKey(name: 'download_url')
  final String? downloadUrl;
  @JsonKey(name: 'created_at')
  final String? createdAt;
  final RecordingCameraModel? camera;

  const RecordingModel({
    required this.id,
    required this.cameraId,
    this.tenantId,
    this.streamId,
    required this.status,
    this.storagePath,
    required this.fileSizeBytes,
    this.fileSizeHuman,
    required this.durationSeconds,
    this.durationHuman,
    this.startedAt,
    this.endedAt,
    this.codec,
    this.resolution,
    this.fps,
    this.playbackUrl,
    this.downloadUrl,
    this.createdAt,
    this.camera,
  });

  factory RecordingModel.fromJson(Map<String, dynamic> json) =>
      _$RecordingModelFromJson(json);
}

@JsonSerializable(createToJson: false)
class RecordingCameraModel {
  final String id;
  final String name;
  final String? slug;

  const RecordingCameraModel({
    required this.id,
    required this.name,
    this.slug,
  });

  factory RecordingCameraModel.fromJson(Map<String, dynamic> json) =>
      _$RecordingCameraModelFromJson(json);
}
