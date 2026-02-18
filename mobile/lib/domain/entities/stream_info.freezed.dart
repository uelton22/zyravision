// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'stream_info.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
  'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models',
);

/// @nodoc
mixin _$StreamInfo {
  String get id => throw _privateConstructorUsedError;
  String? get cameraId => throw _privateConstructorUsedError;
  String get status => throw _privateConstructorUsedError;
  String? get mediamtxPath => throw _privateConstructorUsedError;
  String? get startedAt => throw _privateConstructorUsedError;
  String? get errorMessage => throw _privateConstructorUsedError;
  StreamUrls? get urls => throw _privateConstructorUsedError;

  /// Create a copy of StreamInfo
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $StreamInfoCopyWith<StreamInfo> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $StreamInfoCopyWith<$Res> {
  factory $StreamInfoCopyWith(
    StreamInfo value,
    $Res Function(StreamInfo) then,
  ) = _$StreamInfoCopyWithImpl<$Res, StreamInfo>;
  @useResult
  $Res call({
    String id,
    String? cameraId,
    String status,
    String? mediamtxPath,
    String? startedAt,
    String? errorMessage,
    StreamUrls? urls,
  });

  $StreamUrlsCopyWith<$Res>? get urls;
}

/// @nodoc
class _$StreamInfoCopyWithImpl<$Res, $Val extends StreamInfo>
    implements $StreamInfoCopyWith<$Res> {
  _$StreamInfoCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of StreamInfo
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? cameraId = freezed,
    Object? status = null,
    Object? mediamtxPath = freezed,
    Object? startedAt = freezed,
    Object? errorMessage = freezed,
    Object? urls = freezed,
  }) {
    return _then(
      _value.copyWith(
            id:
                null == id
                    ? _value.id
                    : id // ignore: cast_nullable_to_non_nullable
                        as String,
            cameraId:
                freezed == cameraId
                    ? _value.cameraId
                    : cameraId // ignore: cast_nullable_to_non_nullable
                        as String?,
            status:
                null == status
                    ? _value.status
                    : status // ignore: cast_nullable_to_non_nullable
                        as String,
            mediamtxPath:
                freezed == mediamtxPath
                    ? _value.mediamtxPath
                    : mediamtxPath // ignore: cast_nullable_to_non_nullable
                        as String?,
            startedAt:
                freezed == startedAt
                    ? _value.startedAt
                    : startedAt // ignore: cast_nullable_to_non_nullable
                        as String?,
            errorMessage:
                freezed == errorMessage
                    ? _value.errorMessage
                    : errorMessage // ignore: cast_nullable_to_non_nullable
                        as String?,
            urls:
                freezed == urls
                    ? _value.urls
                    : urls // ignore: cast_nullable_to_non_nullable
                        as StreamUrls?,
          )
          as $Val,
    );
  }

  /// Create a copy of StreamInfo
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $StreamUrlsCopyWith<$Res>? get urls {
    if (_value.urls == null) {
      return null;
    }

    return $StreamUrlsCopyWith<$Res>(_value.urls!, (value) {
      return _then(_value.copyWith(urls: value) as $Val);
    });
  }
}

/// @nodoc
abstract class _$$StreamInfoImplCopyWith<$Res>
    implements $StreamInfoCopyWith<$Res> {
  factory _$$StreamInfoImplCopyWith(
    _$StreamInfoImpl value,
    $Res Function(_$StreamInfoImpl) then,
  ) = __$$StreamInfoImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({
    String id,
    String? cameraId,
    String status,
    String? mediamtxPath,
    String? startedAt,
    String? errorMessage,
    StreamUrls? urls,
  });

  @override
  $StreamUrlsCopyWith<$Res>? get urls;
}

/// @nodoc
class __$$StreamInfoImplCopyWithImpl<$Res>
    extends _$StreamInfoCopyWithImpl<$Res, _$StreamInfoImpl>
    implements _$$StreamInfoImplCopyWith<$Res> {
  __$$StreamInfoImplCopyWithImpl(
    _$StreamInfoImpl _value,
    $Res Function(_$StreamInfoImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of StreamInfo
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? cameraId = freezed,
    Object? status = null,
    Object? mediamtxPath = freezed,
    Object? startedAt = freezed,
    Object? errorMessage = freezed,
    Object? urls = freezed,
  }) {
    return _then(
      _$StreamInfoImpl(
        id:
            null == id
                ? _value.id
                : id // ignore: cast_nullable_to_non_nullable
                    as String,
        cameraId:
            freezed == cameraId
                ? _value.cameraId
                : cameraId // ignore: cast_nullable_to_non_nullable
                    as String?,
        status:
            null == status
                ? _value.status
                : status // ignore: cast_nullable_to_non_nullable
                    as String,
        mediamtxPath:
            freezed == mediamtxPath
                ? _value.mediamtxPath
                : mediamtxPath // ignore: cast_nullable_to_non_nullable
                    as String?,
        startedAt:
            freezed == startedAt
                ? _value.startedAt
                : startedAt // ignore: cast_nullable_to_non_nullable
                    as String?,
        errorMessage:
            freezed == errorMessage
                ? _value.errorMessage
                : errorMessage // ignore: cast_nullable_to_non_nullable
                    as String?,
        urls:
            freezed == urls
                ? _value.urls
                : urls // ignore: cast_nullable_to_non_nullable
                    as StreamUrls?,
      ),
    );
  }
}

/// @nodoc

class _$StreamInfoImpl implements _StreamInfo {
  const _$StreamInfoImpl({
    required this.id,
    this.cameraId,
    this.status = 'inactive',
    this.mediamtxPath,
    this.startedAt,
    this.errorMessage,
    this.urls,
  });

  @override
  final String id;
  @override
  final String? cameraId;
  @override
  @JsonKey()
  final String status;
  @override
  final String? mediamtxPath;
  @override
  final String? startedAt;
  @override
  final String? errorMessage;
  @override
  final StreamUrls? urls;

  @override
  String toString() {
    return 'StreamInfo(id: $id, cameraId: $cameraId, status: $status, mediamtxPath: $mediamtxPath, startedAt: $startedAt, errorMessage: $errorMessage, urls: $urls)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$StreamInfoImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.cameraId, cameraId) ||
                other.cameraId == cameraId) &&
            (identical(other.status, status) || other.status == status) &&
            (identical(other.mediamtxPath, mediamtxPath) ||
                other.mediamtxPath == mediamtxPath) &&
            (identical(other.startedAt, startedAt) ||
                other.startedAt == startedAt) &&
            (identical(other.errorMessage, errorMessage) ||
                other.errorMessage == errorMessage) &&
            (identical(other.urls, urls) || other.urls == urls));
  }

  @override
  int get hashCode => Object.hash(
    runtimeType,
    id,
    cameraId,
    status,
    mediamtxPath,
    startedAt,
    errorMessage,
    urls,
  );

  /// Create a copy of StreamInfo
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$StreamInfoImplCopyWith<_$StreamInfoImpl> get copyWith =>
      __$$StreamInfoImplCopyWithImpl<_$StreamInfoImpl>(this, _$identity);
}

abstract class _StreamInfo implements StreamInfo {
  const factory _StreamInfo({
    required final String id,
    final String? cameraId,
    final String status,
    final String? mediamtxPath,
    final String? startedAt,
    final String? errorMessage,
    final StreamUrls? urls,
  }) = _$StreamInfoImpl;

  @override
  String get id;
  @override
  String? get cameraId;
  @override
  String get status;
  @override
  String? get mediamtxPath;
  @override
  String? get startedAt;
  @override
  String? get errorMessage;
  @override
  StreamUrls? get urls;

  /// Create a copy of StreamInfo
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$StreamInfoImplCopyWith<_$StreamInfoImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
mixin _$StreamUrls {
  String? get hls => throw _privateConstructorUsedError;
  String? get webrtc => throw _privateConstructorUsedError;
  String? get rtsp => throw _privateConstructorUsedError;

  /// Create a copy of StreamUrls
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $StreamUrlsCopyWith<StreamUrls> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $StreamUrlsCopyWith<$Res> {
  factory $StreamUrlsCopyWith(
    StreamUrls value,
    $Res Function(StreamUrls) then,
  ) = _$StreamUrlsCopyWithImpl<$Res, StreamUrls>;
  @useResult
  $Res call({String? hls, String? webrtc, String? rtsp});
}

/// @nodoc
class _$StreamUrlsCopyWithImpl<$Res, $Val extends StreamUrls>
    implements $StreamUrlsCopyWith<$Res> {
  _$StreamUrlsCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of StreamUrls
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? hls = freezed,
    Object? webrtc = freezed,
    Object? rtsp = freezed,
  }) {
    return _then(
      _value.copyWith(
            hls:
                freezed == hls
                    ? _value.hls
                    : hls // ignore: cast_nullable_to_non_nullable
                        as String?,
            webrtc:
                freezed == webrtc
                    ? _value.webrtc
                    : webrtc // ignore: cast_nullable_to_non_nullable
                        as String?,
            rtsp:
                freezed == rtsp
                    ? _value.rtsp
                    : rtsp // ignore: cast_nullable_to_non_nullable
                        as String?,
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$StreamUrlsImplCopyWith<$Res>
    implements $StreamUrlsCopyWith<$Res> {
  factory _$$StreamUrlsImplCopyWith(
    _$StreamUrlsImpl value,
    $Res Function(_$StreamUrlsImpl) then,
  ) = __$$StreamUrlsImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({String? hls, String? webrtc, String? rtsp});
}

/// @nodoc
class __$$StreamUrlsImplCopyWithImpl<$Res>
    extends _$StreamUrlsCopyWithImpl<$Res, _$StreamUrlsImpl>
    implements _$$StreamUrlsImplCopyWith<$Res> {
  __$$StreamUrlsImplCopyWithImpl(
    _$StreamUrlsImpl _value,
    $Res Function(_$StreamUrlsImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of StreamUrls
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? hls = freezed,
    Object? webrtc = freezed,
    Object? rtsp = freezed,
  }) {
    return _then(
      _$StreamUrlsImpl(
        hls:
            freezed == hls
                ? _value.hls
                : hls // ignore: cast_nullable_to_non_nullable
                    as String?,
        webrtc:
            freezed == webrtc
                ? _value.webrtc
                : webrtc // ignore: cast_nullable_to_non_nullable
                    as String?,
        rtsp:
            freezed == rtsp
                ? _value.rtsp
                : rtsp // ignore: cast_nullable_to_non_nullable
                    as String?,
      ),
    );
  }
}

/// @nodoc

class _$StreamUrlsImpl implements _StreamUrls {
  const _$StreamUrlsImpl({this.hls, this.webrtc, this.rtsp});

  @override
  final String? hls;
  @override
  final String? webrtc;
  @override
  final String? rtsp;

  @override
  String toString() {
    return 'StreamUrls(hls: $hls, webrtc: $webrtc, rtsp: $rtsp)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$StreamUrlsImpl &&
            (identical(other.hls, hls) || other.hls == hls) &&
            (identical(other.webrtc, webrtc) || other.webrtc == webrtc) &&
            (identical(other.rtsp, rtsp) || other.rtsp == rtsp));
  }

  @override
  int get hashCode => Object.hash(runtimeType, hls, webrtc, rtsp);

  /// Create a copy of StreamUrls
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$StreamUrlsImplCopyWith<_$StreamUrlsImpl> get copyWith =>
      __$$StreamUrlsImplCopyWithImpl<_$StreamUrlsImpl>(this, _$identity);
}

abstract class _StreamUrls implements StreamUrls {
  const factory _StreamUrls({
    final String? hls,
    final String? webrtc,
    final String? rtsp,
  }) = _$StreamUrlsImpl;

  @override
  String? get hls;
  @override
  String? get webrtc;
  @override
  String? get rtsp;

  /// Create a copy of StreamUrls
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$StreamUrlsImplCopyWith<_$StreamUrlsImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
