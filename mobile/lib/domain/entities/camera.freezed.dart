// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'camera.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
  'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models',
);

/// @nodoc
mixin _$Camera {
  String get id => throw _privateConstructorUsedError;
  String get name => throw _privateConstructorUsedError;
  String? get slug => throw _privateConstructorUsedError;
  String? get manufacturer => throw _privateConstructorUsedError;
  String? get model => throw _privateConstructorUsedError;
  String? get ipAddress => throw _privateConstructorUsedError;
  CameraStatus get status => throw _privateConstructorUsedError;
  bool get isOnline => throw _privateConstructorUsedError;
  bool get isRecording => throw _privateConstructorUsedError;
  String? get codec => throw _privateConstructorUsedError;
  String? get resolution => throw _privateConstructorUsedError;
  int? get fps => throw _privateConstructorUsedError;
  String? get snapshotUrl => throw _privateConstructorUsedError;
  String? get recordingQuality => throw _privateConstructorUsedError;
  StreamInfo? get stream => throw _privateConstructorUsedError;
  String? get groupId => throw _privateConstructorUsedError;
  String? get groupName => throw _privateConstructorUsedError;
  String? get location => throw _privateConstructorUsedError;
  DateTime? get createdAt => throw _privateConstructorUsedError;

  /// Create a copy of Camera
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $CameraCopyWith<Camera> get copyWith => throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $CameraCopyWith<$Res> {
  factory $CameraCopyWith(Camera value, $Res Function(Camera) then) =
      _$CameraCopyWithImpl<$Res, Camera>;
  @useResult
  $Res call({
    String id,
    String name,
    String? slug,
    String? manufacturer,
    String? model,
    String? ipAddress,
    CameraStatus status,
    bool isOnline,
    bool isRecording,
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
  });

  $StreamInfoCopyWith<$Res>? get stream;
}

/// @nodoc
class _$CameraCopyWithImpl<$Res, $Val extends Camera>
    implements $CameraCopyWith<$Res> {
  _$CameraCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of Camera
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = null,
    Object? slug = freezed,
    Object? manufacturer = freezed,
    Object? model = freezed,
    Object? ipAddress = freezed,
    Object? status = null,
    Object? isOnline = null,
    Object? isRecording = null,
    Object? codec = freezed,
    Object? resolution = freezed,
    Object? fps = freezed,
    Object? snapshotUrl = freezed,
    Object? recordingQuality = freezed,
    Object? stream = freezed,
    Object? groupId = freezed,
    Object? groupName = freezed,
    Object? location = freezed,
    Object? createdAt = freezed,
  }) {
    return _then(
      _value.copyWith(
            id:
                null == id
                    ? _value.id
                    : id // ignore: cast_nullable_to_non_nullable
                        as String,
            name:
                null == name
                    ? _value.name
                    : name // ignore: cast_nullable_to_non_nullable
                        as String,
            slug:
                freezed == slug
                    ? _value.slug
                    : slug // ignore: cast_nullable_to_non_nullable
                        as String?,
            manufacturer:
                freezed == manufacturer
                    ? _value.manufacturer
                    : manufacturer // ignore: cast_nullable_to_non_nullable
                        as String?,
            model:
                freezed == model
                    ? _value.model
                    : model // ignore: cast_nullable_to_non_nullable
                        as String?,
            ipAddress:
                freezed == ipAddress
                    ? _value.ipAddress
                    : ipAddress // ignore: cast_nullable_to_non_nullable
                        as String?,
            status:
                null == status
                    ? _value.status
                    : status // ignore: cast_nullable_to_non_nullable
                        as CameraStatus,
            isOnline:
                null == isOnline
                    ? _value.isOnline
                    : isOnline // ignore: cast_nullable_to_non_nullable
                        as bool,
            isRecording:
                null == isRecording
                    ? _value.isRecording
                    : isRecording // ignore: cast_nullable_to_non_nullable
                        as bool,
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
            snapshotUrl:
                freezed == snapshotUrl
                    ? _value.snapshotUrl
                    : snapshotUrl // ignore: cast_nullable_to_non_nullable
                        as String?,
            recordingQuality:
                freezed == recordingQuality
                    ? _value.recordingQuality
                    : recordingQuality // ignore: cast_nullable_to_non_nullable
                        as String?,
            stream:
                freezed == stream
                    ? _value.stream
                    : stream // ignore: cast_nullable_to_non_nullable
                        as StreamInfo?,
            groupId:
                freezed == groupId
                    ? _value.groupId
                    : groupId // ignore: cast_nullable_to_non_nullable
                        as String?,
            groupName:
                freezed == groupName
                    ? _value.groupName
                    : groupName // ignore: cast_nullable_to_non_nullable
                        as String?,
            location:
                freezed == location
                    ? _value.location
                    : location // ignore: cast_nullable_to_non_nullable
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

  /// Create a copy of Camera
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $StreamInfoCopyWith<$Res>? get stream {
    if (_value.stream == null) {
      return null;
    }

    return $StreamInfoCopyWith<$Res>(_value.stream!, (value) {
      return _then(_value.copyWith(stream: value) as $Val);
    });
  }
}

/// @nodoc
abstract class _$$CameraImplCopyWith<$Res> implements $CameraCopyWith<$Res> {
  factory _$$CameraImplCopyWith(
    _$CameraImpl value,
    $Res Function(_$CameraImpl) then,
  ) = __$$CameraImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({
    String id,
    String name,
    String? slug,
    String? manufacturer,
    String? model,
    String? ipAddress,
    CameraStatus status,
    bool isOnline,
    bool isRecording,
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
  });

  @override
  $StreamInfoCopyWith<$Res>? get stream;
}

/// @nodoc
class __$$CameraImplCopyWithImpl<$Res>
    extends _$CameraCopyWithImpl<$Res, _$CameraImpl>
    implements _$$CameraImplCopyWith<$Res> {
  __$$CameraImplCopyWithImpl(
    _$CameraImpl _value,
    $Res Function(_$CameraImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of Camera
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? name = null,
    Object? slug = freezed,
    Object? manufacturer = freezed,
    Object? model = freezed,
    Object? ipAddress = freezed,
    Object? status = null,
    Object? isOnline = null,
    Object? isRecording = null,
    Object? codec = freezed,
    Object? resolution = freezed,
    Object? fps = freezed,
    Object? snapshotUrl = freezed,
    Object? recordingQuality = freezed,
    Object? stream = freezed,
    Object? groupId = freezed,
    Object? groupName = freezed,
    Object? location = freezed,
    Object? createdAt = freezed,
  }) {
    return _then(
      _$CameraImpl(
        id:
            null == id
                ? _value.id
                : id // ignore: cast_nullable_to_non_nullable
                    as String,
        name:
            null == name
                ? _value.name
                : name // ignore: cast_nullable_to_non_nullable
                    as String,
        slug:
            freezed == slug
                ? _value.slug
                : slug // ignore: cast_nullable_to_non_nullable
                    as String?,
        manufacturer:
            freezed == manufacturer
                ? _value.manufacturer
                : manufacturer // ignore: cast_nullable_to_non_nullable
                    as String?,
        model:
            freezed == model
                ? _value.model
                : model // ignore: cast_nullable_to_non_nullable
                    as String?,
        ipAddress:
            freezed == ipAddress
                ? _value.ipAddress
                : ipAddress // ignore: cast_nullable_to_non_nullable
                    as String?,
        status:
            null == status
                ? _value.status
                : status // ignore: cast_nullable_to_non_nullable
                    as CameraStatus,
        isOnline:
            null == isOnline
                ? _value.isOnline
                : isOnline // ignore: cast_nullable_to_non_nullable
                    as bool,
        isRecording:
            null == isRecording
                ? _value.isRecording
                : isRecording // ignore: cast_nullable_to_non_nullable
                    as bool,
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
        snapshotUrl:
            freezed == snapshotUrl
                ? _value.snapshotUrl
                : snapshotUrl // ignore: cast_nullable_to_non_nullable
                    as String?,
        recordingQuality:
            freezed == recordingQuality
                ? _value.recordingQuality
                : recordingQuality // ignore: cast_nullable_to_non_nullable
                    as String?,
        stream:
            freezed == stream
                ? _value.stream
                : stream // ignore: cast_nullable_to_non_nullable
                    as StreamInfo?,
        groupId:
            freezed == groupId
                ? _value.groupId
                : groupId // ignore: cast_nullable_to_non_nullable
                    as String?,
        groupName:
            freezed == groupName
                ? _value.groupName
                : groupName // ignore: cast_nullable_to_non_nullable
                    as String?,
        location:
            freezed == location
                ? _value.location
                : location // ignore: cast_nullable_to_non_nullable
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

class _$CameraImpl implements _Camera {
  const _$CameraImpl({
    required this.id,
    required this.name,
    this.slug,
    this.manufacturer,
    this.model,
    this.ipAddress,
    this.status = CameraStatus.unknown,
    this.isOnline = false,
    this.isRecording = false,
    this.codec,
    this.resolution,
    this.fps,
    this.snapshotUrl,
    this.recordingQuality,
    this.stream,
    this.groupId,
    this.groupName,
    this.location,
    this.createdAt,
  });

  @override
  final String id;
  @override
  final String name;
  @override
  final String? slug;
  @override
  final String? manufacturer;
  @override
  final String? model;
  @override
  final String? ipAddress;
  @override
  @JsonKey()
  final CameraStatus status;
  @override
  @JsonKey()
  final bool isOnline;
  @override
  @JsonKey()
  final bool isRecording;
  @override
  final String? codec;
  @override
  final String? resolution;
  @override
  final int? fps;
  @override
  final String? snapshotUrl;
  @override
  final String? recordingQuality;
  @override
  final StreamInfo? stream;
  @override
  final String? groupId;
  @override
  final String? groupName;
  @override
  final String? location;
  @override
  final DateTime? createdAt;

  @override
  String toString() {
    return 'Camera(id: $id, name: $name, slug: $slug, manufacturer: $manufacturer, model: $model, ipAddress: $ipAddress, status: $status, isOnline: $isOnline, isRecording: $isRecording, codec: $codec, resolution: $resolution, fps: $fps, snapshotUrl: $snapshotUrl, recordingQuality: $recordingQuality, stream: $stream, groupId: $groupId, groupName: $groupName, location: $location, createdAt: $createdAt)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$CameraImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.name, name) || other.name == name) &&
            (identical(other.slug, slug) || other.slug == slug) &&
            (identical(other.manufacturer, manufacturer) ||
                other.manufacturer == manufacturer) &&
            (identical(other.model, model) || other.model == model) &&
            (identical(other.ipAddress, ipAddress) ||
                other.ipAddress == ipAddress) &&
            (identical(other.status, status) || other.status == status) &&
            (identical(other.isOnline, isOnline) ||
                other.isOnline == isOnline) &&
            (identical(other.isRecording, isRecording) ||
                other.isRecording == isRecording) &&
            (identical(other.codec, codec) || other.codec == codec) &&
            (identical(other.resolution, resolution) ||
                other.resolution == resolution) &&
            (identical(other.fps, fps) || other.fps == fps) &&
            (identical(other.snapshotUrl, snapshotUrl) ||
                other.snapshotUrl == snapshotUrl) &&
            (identical(other.recordingQuality, recordingQuality) ||
                other.recordingQuality == recordingQuality) &&
            (identical(other.stream, stream) || other.stream == stream) &&
            (identical(other.groupId, groupId) || other.groupId == groupId) &&
            (identical(other.groupName, groupName) ||
                other.groupName == groupName) &&
            (identical(other.location, location) ||
                other.location == location) &&
            (identical(other.createdAt, createdAt) ||
                other.createdAt == createdAt));
  }

  @override
  int get hashCode => Object.hashAll([
    runtimeType,
    id,
    name,
    slug,
    manufacturer,
    model,
    ipAddress,
    status,
    isOnline,
    isRecording,
    codec,
    resolution,
    fps,
    snapshotUrl,
    recordingQuality,
    stream,
    groupId,
    groupName,
    location,
    createdAt,
  ]);

  /// Create a copy of Camera
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$CameraImplCopyWith<_$CameraImpl> get copyWith =>
      __$$CameraImplCopyWithImpl<_$CameraImpl>(this, _$identity);
}

abstract class _Camera implements Camera {
  const factory _Camera({
    required final String id,
    required final String name,
    final String? slug,
    final String? manufacturer,
    final String? model,
    final String? ipAddress,
    final CameraStatus status,
    final bool isOnline,
    final bool isRecording,
    final String? codec,
    final String? resolution,
    final int? fps,
    final String? snapshotUrl,
    final String? recordingQuality,
    final StreamInfo? stream,
    final String? groupId,
    final String? groupName,
    final String? location,
    final DateTime? createdAt,
  }) = _$CameraImpl;

  @override
  String get id;
  @override
  String get name;
  @override
  String? get slug;
  @override
  String? get manufacturer;
  @override
  String? get model;
  @override
  String? get ipAddress;
  @override
  CameraStatus get status;
  @override
  bool get isOnline;
  @override
  bool get isRecording;
  @override
  String? get codec;
  @override
  String? get resolution;
  @override
  int? get fps;
  @override
  String? get snapshotUrl;
  @override
  String? get recordingQuality;
  @override
  StreamInfo? get stream;
  @override
  String? get groupId;
  @override
  String? get groupName;
  @override
  String? get location;
  @override
  DateTime? get createdAt;

  /// Create a copy of Camera
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$CameraImplCopyWith<_$CameraImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
