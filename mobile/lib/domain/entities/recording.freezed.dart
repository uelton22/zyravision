// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'recording.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
  'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models',
);

/// @nodoc
mixin _$Recording {
  String get id => throw _privateConstructorUsedError;
  String get cameraId => throw _privateConstructorUsedError;
  String? get tenantId => throw _privateConstructorUsedError;
  String? get streamId => throw _privateConstructorUsedError;
  RecordingStatus get status => throw _privateConstructorUsedError;
  String? get storagePath => throw _privateConstructorUsedError;
  int get fileSizeBytes => throw _privateConstructorUsedError;
  String? get fileSizeHuman => throw _privateConstructorUsedError;
  int get durationSeconds => throw _privateConstructorUsedError;
  String? get durationHuman => throw _privateConstructorUsedError;
  DateTime? get startedAt => throw _privateConstructorUsedError;
  DateTime? get endedAt => throw _privateConstructorUsedError;
  String? get codec => throw _privateConstructorUsedError;
  String? get resolution => throw _privateConstructorUsedError;
  int? get fps => throw _privateConstructorUsedError;
  String? get cameraName => throw _privateConstructorUsedError;
  String? get playbackUrl => throw _privateConstructorUsedError;
  String? get downloadUrl => throw _privateConstructorUsedError;
  DateTime? get createdAt => throw _privateConstructorUsedError;

  /// Create a copy of Recording
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $RecordingCopyWith<Recording> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $RecordingCopyWith<$Res> {
  factory $RecordingCopyWith(Recording value, $Res Function(Recording) then) =
      _$RecordingCopyWithImpl<$Res, Recording>;
  @useResult
  $Res call({
    String id,
    String cameraId,
    String? tenantId,
    String? streamId,
    RecordingStatus status,
    String? storagePath,
    int fileSizeBytes,
    String? fileSizeHuman,
    int durationSeconds,
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
  });
}

/// @nodoc
class _$RecordingCopyWithImpl<$Res, $Val extends Recording>
    implements $RecordingCopyWith<$Res> {
  _$RecordingCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of Recording
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? cameraId = null,
    Object? tenantId = freezed,
    Object? streamId = freezed,
    Object? status = null,
    Object? storagePath = freezed,
    Object? fileSizeBytes = null,
    Object? fileSizeHuman = freezed,
    Object? durationSeconds = null,
    Object? durationHuman = freezed,
    Object? startedAt = freezed,
    Object? endedAt = freezed,
    Object? codec = freezed,
    Object? resolution = freezed,
    Object? fps = freezed,
    Object? cameraName = freezed,
    Object? playbackUrl = freezed,
    Object? downloadUrl = freezed,
    Object? createdAt = freezed,
  }) {
    return _then(
      _value.copyWith(
            id:
                null == id
                    ? _value.id
                    : id // ignore: cast_nullable_to_non_nullable
                        as String,
            cameraId:
                null == cameraId
                    ? _value.cameraId
                    : cameraId // ignore: cast_nullable_to_non_nullable
                        as String,
            tenantId:
                freezed == tenantId
                    ? _value.tenantId
                    : tenantId // ignore: cast_nullable_to_non_nullable
                        as String?,
            streamId:
                freezed == streamId
                    ? _value.streamId
                    : streamId // ignore: cast_nullable_to_non_nullable
                        as String?,
            status:
                null == status
                    ? _value.status
                    : status // ignore: cast_nullable_to_non_nullable
                        as RecordingStatus,
            storagePath:
                freezed == storagePath
                    ? _value.storagePath
                    : storagePath // ignore: cast_nullable_to_non_nullable
                        as String?,
            fileSizeBytes:
                null == fileSizeBytes
                    ? _value.fileSizeBytes
                    : fileSizeBytes // ignore: cast_nullable_to_non_nullable
                        as int,
            fileSizeHuman:
                freezed == fileSizeHuman
                    ? _value.fileSizeHuman
                    : fileSizeHuman // ignore: cast_nullable_to_non_nullable
                        as String?,
            durationSeconds:
                null == durationSeconds
                    ? _value.durationSeconds
                    : durationSeconds // ignore: cast_nullable_to_non_nullable
                        as int,
            durationHuman:
                freezed == durationHuman
                    ? _value.durationHuman
                    : durationHuman // ignore: cast_nullable_to_non_nullable
                        as String?,
            startedAt:
                freezed == startedAt
                    ? _value.startedAt
                    : startedAt // ignore: cast_nullable_to_non_nullable
                        as DateTime?,
            endedAt:
                freezed == endedAt
                    ? _value.endedAt
                    : endedAt // ignore: cast_nullable_to_non_nullable
                        as DateTime?,
            codec:
                freezed == codec
                    ? _value.codec
                    : codec // ignore: cast_nullable_to_non_nullable
                        as String?,
            resolution:
                freezed == resolution
                    ? _value.resolution
                    : resolution // ignore: cast_nullable_to_non_nullable
                        as String?,
            fps:
                freezed == fps
                    ? _value.fps
                    : fps // ignore: cast_nullable_to_non_nullable
                        as int?,
            cameraName:
                freezed == cameraName
                    ? _value.cameraName
                    : cameraName // ignore: cast_nullable_to_non_nullable
                        as String?,
            playbackUrl:
                freezed == playbackUrl
                    ? _value.playbackUrl
                    : playbackUrl // ignore: cast_nullable_to_non_nullable
                        as String?,
            downloadUrl:
                freezed == downloadUrl
                    ? _value.downloadUrl
                    : downloadUrl // ignore: cast_nullable_to_non_nullable
                        as String?,
            createdAt:
                freezed == createdAt
                    ? _value.createdAt
                    : createdAt // ignore: cast_nullable_to_non_nullable
                        as DateTime?,
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$RecordingImplCopyWith<$Res>
    implements $RecordingCopyWith<$Res> {
  factory _$$RecordingImplCopyWith(
    _$RecordingImpl value,
    $Res Function(_$RecordingImpl) then,
  ) = __$$RecordingImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({
    String id,
    String cameraId,
    String? tenantId,
    String? streamId,
    RecordingStatus status,
    String? storagePath,
    int fileSizeBytes,
    String? fileSizeHuman,
    int durationSeconds,
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
  });
}

/// @nodoc
class __$$RecordingImplCopyWithImpl<$Res>
    extends _$RecordingCopyWithImpl<$Res, _$RecordingImpl>
    implements _$$RecordingImplCopyWith<$Res> {
  __$$RecordingImplCopyWithImpl(
    _$RecordingImpl _value,
    $Res Function(_$RecordingImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of Recording
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? cameraId = null,
    Object? tenantId = freezed,
    Object? streamId = freezed,
    Object? status = null,
    Object? storagePath = freezed,
    Object? fileSizeBytes = null,
    Object? fileSizeHuman = freezed,
    Object? durationSeconds = null,
    Object? durationHuman = freezed,
    Object? startedAt = freezed,
    Object? endedAt = freezed,
    Object? codec = freezed,
    Object? resolution = freezed,
    Object? fps = freezed,
    Object? cameraName = freezed,
    Object? playbackUrl = freezed,
    Object? downloadUrl = freezed,
    Object? createdAt = freezed,
  }) {
    return _then(
      _$RecordingImpl(
        id:
            null == id
                ? _value.id
                : id // ignore: cast_nullable_to_non_nullable
                    as String,
        cameraId:
            null == cameraId
                ? _value.cameraId
                : cameraId // ignore: cast_nullable_to_non_nullable
                    as String,
        tenantId:
            freezed == tenantId
                ? _value.tenantId
                : tenantId // ignore: cast_nullable_to_non_nullable
                    as String?,
        streamId:
            freezed == streamId
                ? _value.streamId
                : streamId // ignore: cast_nullable_to_non_nullable
                    as String?,
        status:
            null == status
                ? _value.status
                : status // ignore: cast_nullable_to_non_nullable
                    as RecordingStatus,
        storagePath:
            freezed == storagePath
                ? _value.storagePath
                : storagePath // ignore: cast_nullable_to_non_nullable
                    as String?,
        fileSizeBytes:
            null == fileSizeBytes
                ? _value.fileSizeBytes
                : fileSizeBytes // ignore: cast_nullable_to_non_nullable
                    as int,
        fileSizeHuman:
            freezed == fileSizeHuman
                ? _value.fileSizeHuman
                : fileSizeHuman // ignore: cast_nullable_to_non_nullable
                    as String?,
        durationSeconds:
            null == durationSeconds
                ? _value.durationSeconds
                : durationSeconds // ignore: cast_nullable_to_non_nullable
                    as int,
        durationHuman:
            freezed == durationHuman
                ? _value.durationHuman
                : durationHuman // ignore: cast_nullable_to_non_nullable
                    as String?,
        startedAt:
            freezed == startedAt
                ? _value.startedAt
                : startedAt // ignore: cast_nullable_to_non_nullable
                    as DateTime?,
        endedAt:
            freezed == endedAt
                ? _value.endedAt
                : endedAt // ignore: cast_nullable_to_non_nullable
                    as DateTime?,
        codec:
            freezed == codec
                ? _value.codec
                : codec // ignore: cast_nullable_to_non_nullable
                    as String?,
        resolution:
            freezed == resolution
                ? _value.resolution
                : resolution // ignore: cast_nullable_to_non_nullable
                    as String?,
        fps:
            freezed == fps
                ? _value.fps
                : fps // ignore: cast_nullable_to_non_nullable
                    as int?,
        cameraName:
            freezed == cameraName
                ? _value.cameraName
                : cameraName // ignore: cast_nullable_to_non_nullable
                    as String?,
        playbackUrl:
            freezed == playbackUrl
                ? _value.playbackUrl
                : playbackUrl // ignore: cast_nullable_to_non_nullable
                    as String?,
        downloadUrl:
            freezed == downloadUrl
                ? _value.downloadUrl
                : downloadUrl // ignore: cast_nullable_to_non_nullable
                    as String?,
        createdAt:
            freezed == createdAt
                ? _value.createdAt
                : createdAt // ignore: cast_nullable_to_non_nullable
                    as DateTime?,
      ),
    );
  }
}

/// @nodoc

class _$RecordingImpl extends _Recording {
  const _$RecordingImpl({
    required this.id,
    required this.cameraId,
    this.tenantId,
    this.streamId,
    this.status = RecordingStatus.unknown,
    this.storagePath,
    this.fileSizeBytes = 0,
    this.fileSizeHuman,
    this.durationSeconds = 0,
    this.durationHuman,
    this.startedAt,
    this.endedAt,
    this.codec,
    this.resolution,
    this.fps,
    this.cameraName,
    this.playbackUrl,
    this.downloadUrl,
    this.createdAt,
  }) : super._();

  @override
  final String id;
  @override
  final String cameraId;
  @override
  final String? tenantId;
  @override
  final String? streamId;
  @override
  @JsonKey()
  final RecordingStatus status;
  @override
  final String? storagePath;
  @override
  @JsonKey()
  final int fileSizeBytes;
  @override
  final String? fileSizeHuman;
  @override
  @JsonKey()
  final int durationSeconds;
  @override
  final String? durationHuman;
  @override
  final DateTime? startedAt;
  @override
  final DateTime? endedAt;
  @override
  final String? codec;
  @override
  final String? resolution;
  @override
  final int? fps;
  @override
  final String? cameraName;
  @override
  final String? playbackUrl;
  @override
  final String? downloadUrl;
  @override
  final DateTime? createdAt;

  @override
  String toString() {
    return 'Recording(id: $id, cameraId: $cameraId, tenantId: $tenantId, streamId: $streamId, status: $status, storagePath: $storagePath, fileSizeBytes: $fileSizeBytes, fileSizeHuman: $fileSizeHuman, durationSeconds: $durationSeconds, durationHuman: $durationHuman, startedAt: $startedAt, endedAt: $endedAt, codec: $codec, resolution: $resolution, fps: $fps, cameraName: $cameraName, playbackUrl: $playbackUrl, downloadUrl: $downloadUrl, createdAt: $createdAt)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$RecordingImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.cameraId, cameraId) ||
                other.cameraId == cameraId) &&
            (identical(other.tenantId, tenantId) ||
                other.tenantId == tenantId) &&
            (identical(other.streamId, streamId) ||
                other.streamId == streamId) &&
            (identical(other.status, status) || other.status == status) &&
            (identical(other.storagePath, storagePath) ||
                other.storagePath == storagePath) &&
            (identical(other.fileSizeBytes, fileSizeBytes) ||
                other.fileSizeBytes == fileSizeBytes) &&
            (identical(other.fileSizeHuman, fileSizeHuman) ||
                other.fileSizeHuman == fileSizeHuman) &&
            (identical(other.durationSeconds, durationSeconds) ||
                other.durationSeconds == durationSeconds) &&
            (identical(other.durationHuman, durationHuman) ||
                other.durationHuman == durationHuman) &&
            (identical(other.startedAt, startedAt) ||
                other.startedAt == startedAt) &&
            (identical(other.endedAt, endedAt) || other.endedAt == endedAt) &&
            (identical(other.codec, codec) || other.codec == codec) &&
            (identical(other.resolution, resolution) ||
                other.resolution == resolution) &&
            (identical(other.fps, fps) || other.fps == fps) &&
            (identical(other.cameraName, cameraName) ||
                other.cameraName == cameraName) &&
            (identical(other.playbackUrl, playbackUrl) ||
                other.playbackUrl == playbackUrl) &&
            (identical(other.downloadUrl, downloadUrl) ||
                other.downloadUrl == downloadUrl) &&
            (identical(other.createdAt, createdAt) ||
                other.createdAt == createdAt));
  }

  @override
  int get hashCode => Object.hashAll([
    runtimeType,
    id,
    cameraId,
    tenantId,
    streamId,
    status,
    storagePath,
    fileSizeBytes,
    fileSizeHuman,
    durationSeconds,
    durationHuman,
    startedAt,
    endedAt,
    codec,
    resolution,
    fps,
    cameraName,
    playbackUrl,
    downloadUrl,
    createdAt,
  ]);

  /// Create a copy of Recording
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$RecordingImplCopyWith<_$RecordingImpl> get copyWith =>
      __$$RecordingImplCopyWithImpl<_$RecordingImpl>(this, _$identity);
}

abstract class _Recording extends Recording {
  const factory _Recording({
    required final String id,
    required final String cameraId,
    final String? tenantId,
    final String? streamId,
    final RecordingStatus status,
    final String? storagePath,
    final int fileSizeBytes,
    final String? fileSizeHuman,
    final int durationSeconds,
    final String? durationHuman,
    final DateTime? startedAt,
    final DateTime? endedAt,
    final String? codec,
    final String? resolution,
    final int? fps,
    final String? cameraName,
    final String? playbackUrl,
    final String? downloadUrl,
    final DateTime? createdAt,
  }) = _$RecordingImpl;
  const _Recording._() : super._();

  @override
  String get id;
  @override
  String get cameraId;
  @override
  String? get tenantId;
  @override
  String? get streamId;
  @override
  RecordingStatus get status;
  @override
  String? get storagePath;
  @override
  int get fileSizeBytes;
  @override
  String? get fileSizeHuman;
  @override
  int get durationSeconds;
  @override
  String? get durationHuman;
  @override
  DateTime? get startedAt;
  @override
  DateTime? get endedAt;
  @override
  String? get codec;
  @override
  String? get resolution;
  @override
  int? get fps;
  @override
  String? get cameraName;
  @override
  String? get playbackUrl;
  @override
  String? get downloadUrl;
  @override
  DateTime? get createdAt;

  /// Create a copy of Recording
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$RecordingImplCopyWith<_$RecordingImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
