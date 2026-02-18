// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'camera_recording_summary.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
  'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models',
);

/// @nodoc
mixin _$CameraRecordingSummary {
  String get cameraId => throw _privateConstructorUsedError;
  String get cameraName => throw _privateConstructorUsedError;
  String? get cameraSlug => throw _privateConstructorUsedError;
  String? get cameraStatus => throw _privateConstructorUsedError;
  bool get isOnline => throw _privateConstructorUsedError;
  bool get isRecording => throw _privateConstructorUsedError;
  int get totalSegments => throw _privateConstructorUsedError;
  int get totalDurationSeconds => throw _privateConstructorUsedError;
  String? get totalDurationHuman => throw _privateConstructorUsedError;
  double get totalHours => throw _privateConstructorUsedError;
  int get totalSizeBytes => throw _privateConstructorUsedError;
  String? get totalSizeHuman => throw _privateConstructorUsedError;
  int get totalDays => throw _privateConstructorUsedError;
  DateTime? get firstRecordingAt => throw _privateConstructorUsedError;
  DateTime? get lastRecordingAt => throw _privateConstructorUsedError;

  /// Create a copy of CameraRecordingSummary
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $CameraRecordingSummaryCopyWith<CameraRecordingSummary> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $CameraRecordingSummaryCopyWith<$Res> {
  factory $CameraRecordingSummaryCopyWith(
    CameraRecordingSummary value,
    $Res Function(CameraRecordingSummary) then,
  ) = _$CameraRecordingSummaryCopyWithImpl<$Res, CameraRecordingSummary>;
  @useResult
  $Res call({
    String cameraId,
    String cameraName,
    String? cameraSlug,
    String? cameraStatus,
    bool isOnline,
    bool isRecording,
    int totalSegments,
    int totalDurationSeconds,
    String? totalDurationHuman,
    double totalHours,
    int totalSizeBytes,
    String? totalSizeHuman,
    int totalDays,
    DateTime? firstRecordingAt,
    DateTime? lastRecordingAt,
  });
}

/// @nodoc
class _$CameraRecordingSummaryCopyWithImpl<
  $Res,
  $Val extends CameraRecordingSummary
>
    implements $CameraRecordingSummaryCopyWith<$Res> {
  _$CameraRecordingSummaryCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of CameraRecordingSummary
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? cameraId = null,
    Object? cameraName = null,
    Object? cameraSlug = freezed,
    Object? cameraStatus = freezed,
    Object? isOnline = null,
    Object? isRecording = null,
    Object? totalSegments = null,
    Object? totalDurationSeconds = null,
    Object? totalDurationHuman = freezed,
    Object? totalHours = null,
    Object? totalSizeBytes = null,
    Object? totalSizeHuman = freezed,
    Object? totalDays = null,
    Object? firstRecordingAt = freezed,
    Object? lastRecordingAt = freezed,
  }) {
    return _then(
      _value.copyWith(
            cameraId:
                null == cameraId
                    ? _value.cameraId
                    : cameraId // ignore: cast_nullable_to_non_nullable
                        as String,
            cameraName:
                null == cameraName
                    ? _value.cameraName
                    : cameraName // ignore: cast_nullable_to_non_nullable
                        as String,
            cameraSlug:
                freezed == cameraSlug
                    ? _value.cameraSlug
                    : cameraSlug // ignore: cast_nullable_to_non_nullable
                        as String?,
            cameraStatus:
                freezed == cameraStatus
                    ? _value.cameraStatus
                    : cameraStatus // ignore: cast_nullable_to_non_nullable
                        as String?,
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
            totalSegments:
                null == totalSegments
                    ? _value.totalSegments
                    : totalSegments // ignore: cast_nullable_to_non_nullable
                        as int,
            totalDurationSeconds:
                null == totalDurationSeconds
                    ? _value.totalDurationSeconds
                    : totalDurationSeconds // ignore: cast_nullable_to_non_nullable
                        as int,
            totalDurationHuman:
                freezed == totalDurationHuman
                    ? _value.totalDurationHuman
                    : totalDurationHuman // ignore: cast_nullable_to_non_nullable
                        as String?,
            totalHours:
                null == totalHours
                    ? _value.totalHours
                    : totalHours // ignore: cast_nullable_to_non_nullable
                        as double,
            totalSizeBytes:
                null == totalSizeBytes
                    ? _value.totalSizeBytes
                    : totalSizeBytes // ignore: cast_nullable_to_non_nullable
                        as int,
            totalSizeHuman:
                freezed == totalSizeHuman
                    ? _value.totalSizeHuman
                    : totalSizeHuman // ignore: cast_nullable_to_non_nullable
                        as String?,
            totalDays:
                null == totalDays
                    ? _value.totalDays
                    : totalDays // ignore: cast_nullable_to_non_nullable
                        as int,
            firstRecordingAt:
                freezed == firstRecordingAt
                    ? _value.firstRecordingAt
                    : firstRecordingAt // ignore: cast_nullable_to_non_nullable
                        as DateTime?,
            lastRecordingAt:
                freezed == lastRecordingAt
                    ? _value.lastRecordingAt
                    : lastRecordingAt // ignore: cast_nullable_to_non_nullable
                        as DateTime?,
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$CameraRecordingSummaryImplCopyWith<$Res>
    implements $CameraRecordingSummaryCopyWith<$Res> {
  factory _$$CameraRecordingSummaryImplCopyWith(
    _$CameraRecordingSummaryImpl value,
    $Res Function(_$CameraRecordingSummaryImpl) then,
  ) = __$$CameraRecordingSummaryImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({
    String cameraId,
    String cameraName,
    String? cameraSlug,
    String? cameraStatus,
    bool isOnline,
    bool isRecording,
    int totalSegments,
    int totalDurationSeconds,
    String? totalDurationHuman,
    double totalHours,
    int totalSizeBytes,
    String? totalSizeHuman,
    int totalDays,
    DateTime? firstRecordingAt,
    DateTime? lastRecordingAt,
  });
}

/// @nodoc
class __$$CameraRecordingSummaryImplCopyWithImpl<$Res>
    extends
        _$CameraRecordingSummaryCopyWithImpl<$Res, _$CameraRecordingSummaryImpl>
    implements _$$CameraRecordingSummaryImplCopyWith<$Res> {
  __$$CameraRecordingSummaryImplCopyWithImpl(
    _$CameraRecordingSummaryImpl _value,
    $Res Function(_$CameraRecordingSummaryImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of CameraRecordingSummary
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? cameraId = null,
    Object? cameraName = null,
    Object? cameraSlug = freezed,
    Object? cameraStatus = freezed,
    Object? isOnline = null,
    Object? isRecording = null,
    Object? totalSegments = null,
    Object? totalDurationSeconds = null,
    Object? totalDurationHuman = freezed,
    Object? totalHours = null,
    Object? totalSizeBytes = null,
    Object? totalSizeHuman = freezed,
    Object? totalDays = null,
    Object? firstRecordingAt = freezed,
    Object? lastRecordingAt = freezed,
  }) {
    return _then(
      _$CameraRecordingSummaryImpl(
        cameraId:
            null == cameraId
                ? _value.cameraId
                : cameraId // ignore: cast_nullable_to_non_nullable
                    as String,
        cameraName:
            null == cameraName
                ? _value.cameraName
                : cameraName // ignore: cast_nullable_to_non_nullable
                    as String,
        cameraSlug:
            freezed == cameraSlug
                ? _value.cameraSlug
                : cameraSlug // ignore: cast_nullable_to_non_nullable
                    as String?,
        cameraStatus:
            freezed == cameraStatus
                ? _value.cameraStatus
                : cameraStatus // ignore: cast_nullable_to_non_nullable
                    as String?,
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
        totalSegments:
            null == totalSegments
                ? _value.totalSegments
                : totalSegments // ignore: cast_nullable_to_non_nullable
                    as int,
        totalDurationSeconds:
            null == totalDurationSeconds
                ? _value.totalDurationSeconds
                : totalDurationSeconds // ignore: cast_nullable_to_non_nullable
                    as int,
        totalDurationHuman:
            freezed == totalDurationHuman
                ? _value.totalDurationHuman
                : totalDurationHuman // ignore: cast_nullable_to_non_nullable
                    as String?,
        totalHours:
            null == totalHours
                ? _value.totalHours
                : totalHours // ignore: cast_nullable_to_non_nullable
                    as double,
        totalSizeBytes:
            null == totalSizeBytes
                ? _value.totalSizeBytes
                : totalSizeBytes // ignore: cast_nullable_to_non_nullable
                    as int,
        totalSizeHuman:
            freezed == totalSizeHuman
                ? _value.totalSizeHuman
                : totalSizeHuman // ignore: cast_nullable_to_non_nullable
                    as String?,
        totalDays:
            null == totalDays
                ? _value.totalDays
                : totalDays // ignore: cast_nullable_to_non_nullable
                    as int,
        firstRecordingAt:
            freezed == firstRecordingAt
                ? _value.firstRecordingAt
                : firstRecordingAt // ignore: cast_nullable_to_non_nullable
                    as DateTime?,
        lastRecordingAt:
            freezed == lastRecordingAt
                ? _value.lastRecordingAt
                : lastRecordingAt // ignore: cast_nullable_to_non_nullable
                    as DateTime?,
      ),
    );
  }
}

/// @nodoc

class _$CameraRecordingSummaryImpl extends _CameraRecordingSummary {
  const _$CameraRecordingSummaryImpl({
    required this.cameraId,
    required this.cameraName,
    this.cameraSlug,
    this.cameraStatus,
    this.isOnline = false,
    this.isRecording = false,
    this.totalSegments = 0,
    this.totalDurationSeconds = 0,
    this.totalDurationHuman,
    this.totalHours = 0.0,
    this.totalSizeBytes = 0,
    this.totalSizeHuman,
    this.totalDays = 0,
    this.firstRecordingAt,
    this.lastRecordingAt,
  }) : super._();

  @override
  final String cameraId;
  @override
  final String cameraName;
  @override
  final String? cameraSlug;
  @override
  final String? cameraStatus;
  @override
  @JsonKey()
  final bool isOnline;
  @override
  @JsonKey()
  final bool isRecording;
  @override
  @JsonKey()
  final int totalSegments;
  @override
  @JsonKey()
  final int totalDurationSeconds;
  @override
  final String? totalDurationHuman;
  @override
  @JsonKey()
  final double totalHours;
  @override
  @JsonKey()
  final int totalSizeBytes;
  @override
  final String? totalSizeHuman;
  @override
  @JsonKey()
  final int totalDays;
  @override
  final DateTime? firstRecordingAt;
  @override
  final DateTime? lastRecordingAt;

  @override
  String toString() {
    return 'CameraRecordingSummary(cameraId: $cameraId, cameraName: $cameraName, cameraSlug: $cameraSlug, cameraStatus: $cameraStatus, isOnline: $isOnline, isRecording: $isRecording, totalSegments: $totalSegments, totalDurationSeconds: $totalDurationSeconds, totalDurationHuman: $totalDurationHuman, totalHours: $totalHours, totalSizeBytes: $totalSizeBytes, totalSizeHuman: $totalSizeHuman, totalDays: $totalDays, firstRecordingAt: $firstRecordingAt, lastRecordingAt: $lastRecordingAt)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$CameraRecordingSummaryImpl &&
            (identical(other.cameraId, cameraId) ||
                other.cameraId == cameraId) &&
            (identical(other.cameraName, cameraName) ||
                other.cameraName == cameraName) &&
            (identical(other.cameraSlug, cameraSlug) ||
                other.cameraSlug == cameraSlug) &&
            (identical(other.cameraStatus, cameraStatus) ||
                other.cameraStatus == cameraStatus) &&
            (identical(other.isOnline, isOnline) ||
                other.isOnline == isOnline) &&
            (identical(other.isRecording, isRecording) ||
                other.isRecording == isRecording) &&
            (identical(other.totalSegments, totalSegments) ||
                other.totalSegments == totalSegments) &&
            (identical(other.totalDurationSeconds, totalDurationSeconds) ||
                other.totalDurationSeconds == totalDurationSeconds) &&
            (identical(other.totalDurationHuman, totalDurationHuman) ||
                other.totalDurationHuman == totalDurationHuman) &&
            (identical(other.totalHours, totalHours) ||
                other.totalHours == totalHours) &&
            (identical(other.totalSizeBytes, totalSizeBytes) ||
                other.totalSizeBytes == totalSizeBytes) &&
            (identical(other.totalSizeHuman, totalSizeHuman) ||
                other.totalSizeHuman == totalSizeHuman) &&
            (identical(other.totalDays, totalDays) ||
                other.totalDays == totalDays) &&
            (identical(other.firstRecordingAt, firstRecordingAt) ||
                other.firstRecordingAt == firstRecordingAt) &&
            (identical(other.lastRecordingAt, lastRecordingAt) ||
                other.lastRecordingAt == lastRecordingAt));
  }

  @override
  int get hashCode => Object.hash(
    runtimeType,
    cameraId,
    cameraName,
    cameraSlug,
    cameraStatus,
    isOnline,
    isRecording,
    totalSegments,
    totalDurationSeconds,
    totalDurationHuman,
    totalHours,
    totalSizeBytes,
    totalSizeHuman,
    totalDays,
    firstRecordingAt,
    lastRecordingAt,
  );

  /// Create a copy of CameraRecordingSummary
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$CameraRecordingSummaryImplCopyWith<_$CameraRecordingSummaryImpl>
  get copyWith =>
      __$$CameraRecordingSummaryImplCopyWithImpl<_$CameraRecordingSummaryImpl>(
        this,
        _$identity,
      );
}

abstract class _CameraRecordingSummary extends CameraRecordingSummary {
  const factory _CameraRecordingSummary({
    required final String cameraId,
    required final String cameraName,
    final String? cameraSlug,
    final String? cameraStatus,
    final bool isOnline,
    final bool isRecording,
    final int totalSegments,
    final int totalDurationSeconds,
    final String? totalDurationHuman,
    final double totalHours,
    final int totalSizeBytes,
    final String? totalSizeHuman,
    final int totalDays,
    final DateTime? firstRecordingAt,
    final DateTime? lastRecordingAt,
  }) = _$CameraRecordingSummaryImpl;
  const _CameraRecordingSummary._() : super._();

  @override
  String get cameraId;
  @override
  String get cameraName;
  @override
  String? get cameraSlug;
  @override
  String? get cameraStatus;
  @override
  bool get isOnline;
  @override
  bool get isRecording;
  @override
  int get totalSegments;
  @override
  int get totalDurationSeconds;
  @override
  String? get totalDurationHuman;
  @override
  double get totalHours;
  @override
  int get totalSizeBytes;
  @override
  String? get totalSizeHuman;
  @override
  int get totalDays;
  @override
  DateTime? get firstRecordingAt;
  @override
  DateTime? get lastRecordingAt;

  /// Create a copy of CameraRecordingSummary
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$CameraRecordingSummaryImplCopyWith<_$CameraRecordingSummaryImpl>
  get copyWith => throw _privateConstructorUsedError;
}
