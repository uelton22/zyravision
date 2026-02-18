// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'recording_segment.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
  'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models',
);

/// @nodoc
mixin _$RecordingSegment {
  String get id => throw _privateConstructorUsedError;
  String get cameraId => throw _privateConstructorUsedError;
  String? get tenantId => throw _privateConstructorUsedError;
  SegmentStatus get status => throw _privateConstructorUsedError;
  String? get filename => throw _privateConstructorUsedError;
  int get fileSizeBytes => throw _privateConstructorUsedError;
  int get durationSeconds => throw _privateConstructorUsedError;
  DateTime? get startsAt => throw _privateConstructorUsedError;
  DateTime? get endsAt => throw _privateConstructorUsedError;
  String? get codec => throw _privateConstructorUsedError;
  String? get resolution => throw _privateConstructorUsedError;
  int? get fps => throw _privateConstructorUsedError;
  String? get thumbnailPath => throw _privateConstructorUsedError;
  String? get thumbnailUrl => throw _privateConstructorUsedError;
  bool get hasLocal => throw _privateConstructorUsedError;
  bool get hasRemote => throw _privateConstructorUsedError;

  /// Create a copy of RecordingSegment
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $RecordingSegmentCopyWith<RecordingSegment> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $RecordingSegmentCopyWith<$Res> {
  factory $RecordingSegmentCopyWith(
    RecordingSegment value,
    $Res Function(RecordingSegment) then,
  ) = _$RecordingSegmentCopyWithImpl<$Res, RecordingSegment>;
  @useResult
  $Res call({
    String id,
    String cameraId,
    String? tenantId,
    SegmentStatus status,
    String? filename,
    int fileSizeBytes,
    int durationSeconds,
    DateTime? startsAt,
    DateTime? endsAt,
    String? codec,
    String? resolution,
    int? fps,
    String? thumbnailPath,
    String? thumbnailUrl,
    bool hasLocal,
    bool hasRemote,
  });
}

/// @nodoc
class _$RecordingSegmentCopyWithImpl<$Res, $Val extends RecordingSegment>
    implements $RecordingSegmentCopyWith<$Res> {
  _$RecordingSegmentCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of RecordingSegment
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? cameraId = null,
    Object? tenantId = freezed,
    Object? status = null,
    Object? filename = freezed,
    Object? fileSizeBytes = null,
    Object? durationSeconds = null,
    Object? startsAt = freezed,
    Object? endsAt = freezed,
    Object? codec = freezed,
    Object? resolution = freezed,
    Object? fps = freezed,
    Object? thumbnailPath = freezed,
    Object? thumbnailUrl = freezed,
    Object? hasLocal = null,
    Object? hasRemote = null,
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
            status:
                null == status
                    ? _value.status
                    : status // ignore: cast_nullable_to_non_nullable
                        as SegmentStatus,
            filename:
                freezed == filename
                    ? _value.filename
                    : filename // ignore: cast_nullable_to_non_nullable
                        as String?,
            fileSizeBytes:
                null == fileSizeBytes
                    ? _value.fileSizeBytes
                    : fileSizeBytes // ignore: cast_nullable_to_non_nullable
                        as int,
            durationSeconds:
                null == durationSeconds
                    ? _value.durationSeconds
                    : durationSeconds // ignore: cast_nullable_to_non_nullable
                        as int,
            startsAt:
                freezed == startsAt
                    ? _value.startsAt
                    : startsAt // ignore: cast_nullable_to_non_nullable
                        as DateTime?,
            endsAt:
                freezed == endsAt
                    ? _value.endsAt
                    : endsAt // ignore: cast_nullable_to_non_nullable
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
            thumbnailPath:
                freezed == thumbnailPath
                    ? _value.thumbnailPath
                    : thumbnailPath // ignore: cast_nullable_to_non_nullable
                        as String?,
            thumbnailUrl:
                freezed == thumbnailUrl
                    ? _value.thumbnailUrl
                    : thumbnailUrl // ignore: cast_nullable_to_non_nullable
                        as String?,
            hasLocal:
                null == hasLocal
                    ? _value.hasLocal
                    : hasLocal // ignore: cast_nullable_to_non_nullable
                        as bool,
            hasRemote:
                null == hasRemote
                    ? _value.hasRemote
                    : hasRemote // ignore: cast_nullable_to_non_nullable
                        as bool,
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$RecordingSegmentImplCopyWith<$Res>
    implements $RecordingSegmentCopyWith<$Res> {
  factory _$$RecordingSegmentImplCopyWith(
    _$RecordingSegmentImpl value,
    $Res Function(_$RecordingSegmentImpl) then,
  ) = __$$RecordingSegmentImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({
    String id,
    String cameraId,
    String? tenantId,
    SegmentStatus status,
    String? filename,
    int fileSizeBytes,
    int durationSeconds,
    DateTime? startsAt,
    DateTime? endsAt,
    String? codec,
    String? resolution,
    int? fps,
    String? thumbnailPath,
    String? thumbnailUrl,
    bool hasLocal,
    bool hasRemote,
  });
}

/// @nodoc
class __$$RecordingSegmentImplCopyWithImpl<$Res>
    extends _$RecordingSegmentCopyWithImpl<$Res, _$RecordingSegmentImpl>
    implements _$$RecordingSegmentImplCopyWith<$Res> {
  __$$RecordingSegmentImplCopyWithImpl(
    _$RecordingSegmentImpl _value,
    $Res Function(_$RecordingSegmentImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of RecordingSegment
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? cameraId = null,
    Object? tenantId = freezed,
    Object? status = null,
    Object? filename = freezed,
    Object? fileSizeBytes = null,
    Object? durationSeconds = null,
    Object? startsAt = freezed,
    Object? endsAt = freezed,
    Object? codec = freezed,
    Object? resolution = freezed,
    Object? fps = freezed,
    Object? thumbnailPath = freezed,
    Object? thumbnailUrl = freezed,
    Object? hasLocal = null,
    Object? hasRemote = null,
  }) {
    return _then(
      _$RecordingSegmentImpl(
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
        status:
            null == status
                ? _value.status
                : status // ignore: cast_nullable_to_non_nullable
                    as SegmentStatus,
        filename:
            freezed == filename
                ? _value.filename
                : filename // ignore: cast_nullable_to_non_nullable
                    as String?,
        fileSizeBytes:
            null == fileSizeBytes
                ? _value.fileSizeBytes
                : fileSizeBytes // ignore: cast_nullable_to_non_nullable
                    as int,
        durationSeconds:
            null == durationSeconds
                ? _value.durationSeconds
                : durationSeconds // ignore: cast_nullable_to_non_nullable
                    as int,
        startsAt:
            freezed == startsAt
                ? _value.startsAt
                : startsAt // ignore: cast_nullable_to_non_nullable
                    as DateTime?,
        endsAt:
            freezed == endsAt
                ? _value.endsAt
                : endsAt // ignore: cast_nullable_to_non_nullable
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
        thumbnailPath:
            freezed == thumbnailPath
                ? _value.thumbnailPath
                : thumbnailPath // ignore: cast_nullable_to_non_nullable
                    as String?,
        thumbnailUrl:
            freezed == thumbnailUrl
                ? _value.thumbnailUrl
                : thumbnailUrl // ignore: cast_nullable_to_non_nullable
                    as String?,
        hasLocal:
            null == hasLocal
                ? _value.hasLocal
                : hasLocal // ignore: cast_nullable_to_non_nullable
                    as bool,
        hasRemote:
            null == hasRemote
                ? _value.hasRemote
                : hasRemote // ignore: cast_nullable_to_non_nullable
                    as bool,
      ),
    );
  }
}

/// @nodoc

class _$RecordingSegmentImpl extends _RecordingSegment {
  const _$RecordingSegmentImpl({
    required this.id,
    required this.cameraId,
    this.tenantId,
    this.status = SegmentStatus.unknown,
    this.filename,
    this.fileSizeBytes = 0,
    this.durationSeconds = 0,
    this.startsAt,
    this.endsAt,
    this.codec,
    this.resolution,
    this.fps,
    this.thumbnailPath,
    this.thumbnailUrl,
    this.hasLocal = false,
    this.hasRemote = false,
  }) : super._();

  @override
  final String id;
  @override
  final String cameraId;
  @override
  final String? tenantId;
  @override
  @JsonKey()
  final SegmentStatus status;
  @override
  final String? filename;
  @override
  @JsonKey()
  final int fileSizeBytes;
  @override
  @JsonKey()
  final int durationSeconds;
  @override
  final DateTime? startsAt;
  @override
  final DateTime? endsAt;
  @override
  final String? codec;
  @override
  final String? resolution;
  @override
  final int? fps;
  @override
  final String? thumbnailPath;
  @override
  final String? thumbnailUrl;
  @override
  @JsonKey()
  final bool hasLocal;
  @override
  @JsonKey()
  final bool hasRemote;

  @override
  String toString() {
    return 'RecordingSegment(id: $id, cameraId: $cameraId, tenantId: $tenantId, status: $status, filename: $filename, fileSizeBytes: $fileSizeBytes, durationSeconds: $durationSeconds, startsAt: $startsAt, endsAt: $endsAt, codec: $codec, resolution: $resolution, fps: $fps, thumbnailPath: $thumbnailPath, thumbnailUrl: $thumbnailUrl, hasLocal: $hasLocal, hasRemote: $hasRemote)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$RecordingSegmentImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.cameraId, cameraId) ||
                other.cameraId == cameraId) &&
            (identical(other.tenantId, tenantId) ||
                other.tenantId == tenantId) &&
            (identical(other.status, status) || other.status == status) &&
            (identical(other.filename, filename) ||
                other.filename == filename) &&
            (identical(other.fileSizeBytes, fileSizeBytes) ||
                other.fileSizeBytes == fileSizeBytes) &&
            (identical(other.durationSeconds, durationSeconds) ||
                other.durationSeconds == durationSeconds) &&
            (identical(other.startsAt, startsAt) ||
                other.startsAt == startsAt) &&
            (identical(other.endsAt, endsAt) || other.endsAt == endsAt) &&
            (identical(other.codec, codec) || other.codec == codec) &&
            (identical(other.resolution, resolution) ||
                other.resolution == resolution) &&
            (identical(other.fps, fps) || other.fps == fps) &&
            (identical(other.thumbnailPath, thumbnailPath) ||
                other.thumbnailPath == thumbnailPath) &&
            (identical(other.thumbnailUrl, thumbnailUrl) ||
                other.thumbnailUrl == thumbnailUrl) &&
            (identical(other.hasLocal, hasLocal) ||
                other.hasLocal == hasLocal) &&
            (identical(other.hasRemote, hasRemote) ||
                other.hasRemote == hasRemote));
  }

  @override
  int get hashCode => Object.hash(
    runtimeType,
    id,
    cameraId,
    tenantId,
    status,
    filename,
    fileSizeBytes,
    durationSeconds,
    startsAt,
    endsAt,
    codec,
    resolution,
    fps,
    thumbnailPath,
    thumbnailUrl,
    hasLocal,
    hasRemote,
  );

  /// Create a copy of RecordingSegment
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$RecordingSegmentImplCopyWith<_$RecordingSegmentImpl> get copyWith =>
      __$$RecordingSegmentImplCopyWithImpl<_$RecordingSegmentImpl>(
        this,
        _$identity,
      );
}

abstract class _RecordingSegment extends RecordingSegment {
  const factory _RecordingSegment({
    required final String id,
    required final String cameraId,
    final String? tenantId,
    final SegmentStatus status,
    final String? filename,
    final int fileSizeBytes,
    final int durationSeconds,
    final DateTime? startsAt,
    final DateTime? endsAt,
    final String? codec,
    final String? resolution,
    final int? fps,
    final String? thumbnailPath,
    final String? thumbnailUrl,
    final bool hasLocal,
    final bool hasRemote,
  }) = _$RecordingSegmentImpl;
  const _RecordingSegment._() : super._();

  @override
  String get id;
  @override
  String get cameraId;
  @override
  String? get tenantId;
  @override
  SegmentStatus get status;
  @override
  String? get filename;
  @override
  int get fileSizeBytes;
  @override
  int get durationSeconds;
  @override
  DateTime? get startsAt;
  @override
  DateTime? get endsAt;
  @override
  String? get codec;
  @override
  String? get resolution;
  @override
  int? get fps;
  @override
  String? get thumbnailPath;
  @override
  String? get thumbnailUrl;
  @override
  bool get hasLocal;
  @override
  bool get hasRemote;

  /// Create a copy of RecordingSegment
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$RecordingSegmentImplCopyWith<_$RecordingSegmentImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
