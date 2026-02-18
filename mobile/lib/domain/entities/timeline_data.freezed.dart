// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'timeline_data.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
  'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models',
);

/// @nodoc
mixin _$TimelineData {
  String get cameraId => throw _privateConstructorUsedError;
  DateTime get rangeStart => throw _privateConstructorUsedError;
  DateTime get rangeEnd => throw _privateConstructorUsedError;
  List<RecordingSegment> get segments => throw _privateConstructorUsedError;
  List<TimelineRange> get ranges => throw _privateConstructorUsedError;
  int get totalSegments => throw _privateConstructorUsedError;
  int get totalDurationSeconds => throw _privateConstructorUsedError;

  /// Create a copy of TimelineData
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $TimelineDataCopyWith<TimelineData> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $TimelineDataCopyWith<$Res> {
  factory $TimelineDataCopyWith(
    TimelineData value,
    $Res Function(TimelineData) then,
  ) = _$TimelineDataCopyWithImpl<$Res, TimelineData>;
  @useResult
  $Res call({
    String cameraId,
    DateTime rangeStart,
    DateTime rangeEnd,
    List<RecordingSegment> segments,
    List<TimelineRange> ranges,
    int totalSegments,
    int totalDurationSeconds,
  });
}

/// @nodoc
class _$TimelineDataCopyWithImpl<$Res, $Val extends TimelineData>
    implements $TimelineDataCopyWith<$Res> {
  _$TimelineDataCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of TimelineData
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? cameraId = null,
    Object? rangeStart = null,
    Object? rangeEnd = null,
    Object? segments = null,
    Object? ranges = null,
    Object? totalSegments = null,
    Object? totalDurationSeconds = null,
  }) {
    return _then(
      _value.copyWith(
            cameraId:
                null == cameraId
                    ? _value.cameraId
                    : cameraId // ignore: cast_nullable_to_non_nullable
                        as String,
            rangeStart:
                null == rangeStart
                    ? _value.rangeStart
                    : rangeStart // ignore: cast_nullable_to_non_nullable
                        as DateTime,
            rangeEnd:
                null == rangeEnd
                    ? _value.rangeEnd
                    : rangeEnd // ignore: cast_nullable_to_non_nullable
                        as DateTime,
            segments:
                null == segments
                    ? _value.segments
                    : segments // ignore: cast_nullable_to_non_nullable
                        as List<RecordingSegment>,
            ranges:
                null == ranges
                    ? _value.ranges
                    : ranges // ignore: cast_nullable_to_non_nullable
                        as List<TimelineRange>,
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
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$TimelineDataImplCopyWith<$Res>
    implements $TimelineDataCopyWith<$Res> {
  factory _$$TimelineDataImplCopyWith(
    _$TimelineDataImpl value,
    $Res Function(_$TimelineDataImpl) then,
  ) = __$$TimelineDataImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({
    String cameraId,
    DateTime rangeStart,
    DateTime rangeEnd,
    List<RecordingSegment> segments,
    List<TimelineRange> ranges,
    int totalSegments,
    int totalDurationSeconds,
  });
}

/// @nodoc
class __$$TimelineDataImplCopyWithImpl<$Res>
    extends _$TimelineDataCopyWithImpl<$Res, _$TimelineDataImpl>
    implements _$$TimelineDataImplCopyWith<$Res> {
  __$$TimelineDataImplCopyWithImpl(
    _$TimelineDataImpl _value,
    $Res Function(_$TimelineDataImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of TimelineData
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? cameraId = null,
    Object? rangeStart = null,
    Object? rangeEnd = null,
    Object? segments = null,
    Object? ranges = null,
    Object? totalSegments = null,
    Object? totalDurationSeconds = null,
  }) {
    return _then(
      _$TimelineDataImpl(
        cameraId:
            null == cameraId
                ? _value.cameraId
                : cameraId // ignore: cast_nullable_to_non_nullable
                    as String,
        rangeStart:
            null == rangeStart
                ? _value.rangeStart
                : rangeStart // ignore: cast_nullable_to_non_nullable
                    as DateTime,
        rangeEnd:
            null == rangeEnd
                ? _value.rangeEnd
                : rangeEnd // ignore: cast_nullable_to_non_nullable
                    as DateTime,
        segments:
            null == segments
                ? _value._segments
                : segments // ignore: cast_nullable_to_non_nullable
                    as List<RecordingSegment>,
        ranges:
            null == ranges
                ? _value._ranges
                : ranges // ignore: cast_nullable_to_non_nullable
                    as List<TimelineRange>,
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
      ),
    );
  }
}

/// @nodoc

class _$TimelineDataImpl extends _TimelineData {
  const _$TimelineDataImpl({
    required this.cameraId,
    required this.rangeStart,
    required this.rangeEnd,
    final List<RecordingSegment> segments = const [],
    final List<TimelineRange> ranges = const [],
    this.totalSegments = 0,
    this.totalDurationSeconds = 0,
  }) : _segments = segments,
       _ranges = ranges,
       super._();

  @override
  final String cameraId;
  @override
  final DateTime rangeStart;
  @override
  final DateTime rangeEnd;
  final List<RecordingSegment> _segments;
  @override
  @JsonKey()
  List<RecordingSegment> get segments {
    if (_segments is EqualUnmodifiableListView) return _segments;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_segments);
  }

  final List<TimelineRange> _ranges;
  @override
  @JsonKey()
  List<TimelineRange> get ranges {
    if (_ranges is EqualUnmodifiableListView) return _ranges;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_ranges);
  }

  @override
  @JsonKey()
  final int totalSegments;
  @override
  @JsonKey()
  final int totalDurationSeconds;

  @override
  String toString() {
    return 'TimelineData(cameraId: $cameraId, rangeStart: $rangeStart, rangeEnd: $rangeEnd, segments: $segments, ranges: $ranges, totalSegments: $totalSegments, totalDurationSeconds: $totalDurationSeconds)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$TimelineDataImpl &&
            (identical(other.cameraId, cameraId) ||
                other.cameraId == cameraId) &&
            (identical(other.rangeStart, rangeStart) ||
                other.rangeStart == rangeStart) &&
            (identical(other.rangeEnd, rangeEnd) ||
                other.rangeEnd == rangeEnd) &&
            const DeepCollectionEquality().equals(other._segments, _segments) &&
            const DeepCollectionEquality().equals(other._ranges, _ranges) &&
            (identical(other.totalSegments, totalSegments) ||
                other.totalSegments == totalSegments) &&
            (identical(other.totalDurationSeconds, totalDurationSeconds) ||
                other.totalDurationSeconds == totalDurationSeconds));
  }

  @override
  int get hashCode => Object.hash(
    runtimeType,
    cameraId,
    rangeStart,
    rangeEnd,
    const DeepCollectionEquality().hash(_segments),
    const DeepCollectionEquality().hash(_ranges),
    totalSegments,
    totalDurationSeconds,
  );

  /// Create a copy of TimelineData
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$TimelineDataImplCopyWith<_$TimelineDataImpl> get copyWith =>
      __$$TimelineDataImplCopyWithImpl<_$TimelineDataImpl>(this, _$identity);
}

abstract class _TimelineData extends TimelineData {
  const factory _TimelineData({
    required final String cameraId,
    required final DateTime rangeStart,
    required final DateTime rangeEnd,
    final List<RecordingSegment> segments,
    final List<TimelineRange> ranges,
    final int totalSegments,
    final int totalDurationSeconds,
  }) = _$TimelineDataImpl;
  const _TimelineData._() : super._();

  @override
  String get cameraId;
  @override
  DateTime get rangeStart;
  @override
  DateTime get rangeEnd;
  @override
  List<RecordingSegment> get segments;
  @override
  List<TimelineRange> get ranges;
  @override
  int get totalSegments;
  @override
  int get totalDurationSeconds;

  /// Create a copy of TimelineData
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$TimelineDataImplCopyWith<_$TimelineDataImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
mixin _$TimelineRange {
  DateTime get start => throw _privateConstructorUsedError;
  DateTime get end => throw _privateConstructorUsedError;

  /// Create a copy of TimelineRange
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $TimelineRangeCopyWith<TimelineRange> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $TimelineRangeCopyWith<$Res> {
  factory $TimelineRangeCopyWith(
    TimelineRange value,
    $Res Function(TimelineRange) then,
  ) = _$TimelineRangeCopyWithImpl<$Res, TimelineRange>;
  @useResult
  $Res call({DateTime start, DateTime end});
}

/// @nodoc
class _$TimelineRangeCopyWithImpl<$Res, $Val extends TimelineRange>
    implements $TimelineRangeCopyWith<$Res> {
  _$TimelineRangeCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of TimelineRange
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({Object? start = null, Object? end = null}) {
    return _then(
      _value.copyWith(
            start:
                null == start
                    ? _value.start
                    : start // ignore: cast_nullable_to_non_nullable
                        as DateTime,
            end:
                null == end
                    ? _value.end
                    : end // ignore: cast_nullable_to_non_nullable
                        as DateTime,
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$TimelineRangeImplCopyWith<$Res>
    implements $TimelineRangeCopyWith<$Res> {
  factory _$$TimelineRangeImplCopyWith(
    _$TimelineRangeImpl value,
    $Res Function(_$TimelineRangeImpl) then,
  ) = __$$TimelineRangeImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({DateTime start, DateTime end});
}

/// @nodoc
class __$$TimelineRangeImplCopyWithImpl<$Res>
    extends _$TimelineRangeCopyWithImpl<$Res, _$TimelineRangeImpl>
    implements _$$TimelineRangeImplCopyWith<$Res> {
  __$$TimelineRangeImplCopyWithImpl(
    _$TimelineRangeImpl _value,
    $Res Function(_$TimelineRangeImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of TimelineRange
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({Object? start = null, Object? end = null}) {
    return _then(
      _$TimelineRangeImpl(
        start:
            null == start
                ? _value.start
                : start // ignore: cast_nullable_to_non_nullable
                    as DateTime,
        end:
            null == end
                ? _value.end
                : end // ignore: cast_nullable_to_non_nullable
                    as DateTime,
      ),
    );
  }
}

/// @nodoc

class _$TimelineRangeImpl extends _TimelineRange {
  const _$TimelineRangeImpl({required this.start, required this.end})
    : super._();

  @override
  final DateTime start;
  @override
  final DateTime end;

  @override
  String toString() {
    return 'TimelineRange(start: $start, end: $end)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$TimelineRangeImpl &&
            (identical(other.start, start) || other.start == start) &&
            (identical(other.end, end) || other.end == end));
  }

  @override
  int get hashCode => Object.hash(runtimeType, start, end);

  /// Create a copy of TimelineRange
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$TimelineRangeImplCopyWith<_$TimelineRangeImpl> get copyWith =>
      __$$TimelineRangeImplCopyWithImpl<_$TimelineRangeImpl>(this, _$identity);
}

abstract class _TimelineRange extends TimelineRange {
  const factory _TimelineRange({
    required final DateTime start,
    required final DateTime end,
  }) = _$TimelineRangeImpl;
  const _TimelineRange._() : super._();

  @override
  DateTime get start;
  @override
  DateTime get end;

  /// Create a copy of TimelineRange
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$TimelineRangeImplCopyWith<_$TimelineRangeImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
mixin _$PlaybackSegmentInfo {
  String get segmentId => throw _privateConstructorUsedError;
  DateTime get startsAt => throw _privateConstructorUsedError;
  DateTime? get endsAt => throw _privateConstructorUsedError;
  int get durationSeconds => throw _privateConstructorUsedError;
  String get streamUrl => throw _privateConstructorUsedError;
  int get offsetSeconds => throw _privateConstructorUsedError;

  /// Create a copy of PlaybackSegmentInfo
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $PlaybackSegmentInfoCopyWith<PlaybackSegmentInfo> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $PlaybackSegmentInfoCopyWith<$Res> {
  factory $PlaybackSegmentInfoCopyWith(
    PlaybackSegmentInfo value,
    $Res Function(PlaybackSegmentInfo) then,
  ) = _$PlaybackSegmentInfoCopyWithImpl<$Res, PlaybackSegmentInfo>;
  @useResult
  $Res call({
    String segmentId,
    DateTime startsAt,
    DateTime? endsAt,
    int durationSeconds,
    String streamUrl,
    int offsetSeconds,
  });
}

/// @nodoc
class _$PlaybackSegmentInfoCopyWithImpl<$Res, $Val extends PlaybackSegmentInfo>
    implements $PlaybackSegmentInfoCopyWith<$Res> {
  _$PlaybackSegmentInfoCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of PlaybackSegmentInfo
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? segmentId = null,
    Object? startsAt = null,
    Object? endsAt = freezed,
    Object? durationSeconds = null,
    Object? streamUrl = null,
    Object? offsetSeconds = null,
  }) {
    return _then(
      _value.copyWith(
            segmentId:
                null == segmentId
                    ? _value.segmentId
                    : segmentId // ignore: cast_nullable_to_non_nullable
                        as String,
            startsAt:
                null == startsAt
                    ? _value.startsAt
                    : startsAt // ignore: cast_nullable_to_non_nullable
                        as DateTime,
            endsAt:
                freezed == endsAt
                    ? _value.endsAt
                    : endsAt // ignore: cast_nullable_to_non_nullable
                        as DateTime?,
            durationSeconds:
                null == durationSeconds
                    ? _value.durationSeconds
                    : durationSeconds // ignore: cast_nullable_to_non_nullable
                        as int,
            streamUrl:
                null == streamUrl
                    ? _value.streamUrl
                    : streamUrl // ignore: cast_nullable_to_non_nullable
                        as String,
            offsetSeconds:
                null == offsetSeconds
                    ? _value.offsetSeconds
                    : offsetSeconds // ignore: cast_nullable_to_non_nullable
                        as int,
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$PlaybackSegmentInfoImplCopyWith<$Res>
    implements $PlaybackSegmentInfoCopyWith<$Res> {
  factory _$$PlaybackSegmentInfoImplCopyWith(
    _$PlaybackSegmentInfoImpl value,
    $Res Function(_$PlaybackSegmentInfoImpl) then,
  ) = __$$PlaybackSegmentInfoImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({
    String segmentId,
    DateTime startsAt,
    DateTime? endsAt,
    int durationSeconds,
    String streamUrl,
    int offsetSeconds,
  });
}

/// @nodoc
class __$$PlaybackSegmentInfoImplCopyWithImpl<$Res>
    extends _$PlaybackSegmentInfoCopyWithImpl<$Res, _$PlaybackSegmentInfoImpl>
    implements _$$PlaybackSegmentInfoImplCopyWith<$Res> {
  __$$PlaybackSegmentInfoImplCopyWithImpl(
    _$PlaybackSegmentInfoImpl _value,
    $Res Function(_$PlaybackSegmentInfoImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of PlaybackSegmentInfo
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? segmentId = null,
    Object? startsAt = null,
    Object? endsAt = freezed,
    Object? durationSeconds = null,
    Object? streamUrl = null,
    Object? offsetSeconds = null,
  }) {
    return _then(
      _$PlaybackSegmentInfoImpl(
        segmentId:
            null == segmentId
                ? _value.segmentId
                : segmentId // ignore: cast_nullable_to_non_nullable
                    as String,
        startsAt:
            null == startsAt
                ? _value.startsAt
                : startsAt // ignore: cast_nullable_to_non_nullable
                    as DateTime,
        endsAt:
            freezed == endsAt
                ? _value.endsAt
                : endsAt // ignore: cast_nullable_to_non_nullable
                    as DateTime?,
        durationSeconds:
            null == durationSeconds
                ? _value.durationSeconds
                : durationSeconds // ignore: cast_nullable_to_non_nullable
                    as int,
        streamUrl:
            null == streamUrl
                ? _value.streamUrl
                : streamUrl // ignore: cast_nullable_to_non_nullable
                    as String,
        offsetSeconds:
            null == offsetSeconds
                ? _value.offsetSeconds
                : offsetSeconds // ignore: cast_nullable_to_non_nullable
                    as int,
      ),
    );
  }
}

/// @nodoc

class _$PlaybackSegmentInfoImpl implements _PlaybackSegmentInfo {
  const _$PlaybackSegmentInfoImpl({
    required this.segmentId,
    required this.startsAt,
    this.endsAt,
    this.durationSeconds = 0,
    required this.streamUrl,
    this.offsetSeconds = 0,
  });

  @override
  final String segmentId;
  @override
  final DateTime startsAt;
  @override
  final DateTime? endsAt;
  @override
  @JsonKey()
  final int durationSeconds;
  @override
  final String streamUrl;
  @override
  @JsonKey()
  final int offsetSeconds;

  @override
  String toString() {
    return 'PlaybackSegmentInfo(segmentId: $segmentId, startsAt: $startsAt, endsAt: $endsAt, durationSeconds: $durationSeconds, streamUrl: $streamUrl, offsetSeconds: $offsetSeconds)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$PlaybackSegmentInfoImpl &&
            (identical(other.segmentId, segmentId) ||
                other.segmentId == segmentId) &&
            (identical(other.startsAt, startsAt) ||
                other.startsAt == startsAt) &&
            (identical(other.endsAt, endsAt) || other.endsAt == endsAt) &&
            (identical(other.durationSeconds, durationSeconds) ||
                other.durationSeconds == durationSeconds) &&
            (identical(other.streamUrl, streamUrl) ||
                other.streamUrl == streamUrl) &&
            (identical(other.offsetSeconds, offsetSeconds) ||
                other.offsetSeconds == offsetSeconds));
  }

  @override
  int get hashCode => Object.hash(
    runtimeType,
    segmentId,
    startsAt,
    endsAt,
    durationSeconds,
    streamUrl,
    offsetSeconds,
  );

  /// Create a copy of PlaybackSegmentInfo
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$PlaybackSegmentInfoImplCopyWith<_$PlaybackSegmentInfoImpl> get copyWith =>
      __$$PlaybackSegmentInfoImplCopyWithImpl<_$PlaybackSegmentInfoImpl>(
        this,
        _$identity,
      );
}

abstract class _PlaybackSegmentInfo implements PlaybackSegmentInfo {
  const factory _PlaybackSegmentInfo({
    required final String segmentId,
    required final DateTime startsAt,
    final DateTime? endsAt,
    final int durationSeconds,
    required final String streamUrl,
    final int offsetSeconds,
  }) = _$PlaybackSegmentInfoImpl;

  @override
  String get segmentId;
  @override
  DateTime get startsAt;
  @override
  DateTime? get endsAt;
  @override
  int get durationSeconds;
  @override
  String get streamUrl;
  @override
  int get offsetSeconds;

  /// Create a copy of PlaybackSegmentInfo
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$PlaybackSegmentInfoImplCopyWith<_$PlaybackSegmentInfoImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
mixin _$AvailableDate {
  DateTime get date => throw _privateConstructorUsedError;
  int get segmentCount => throw _privateConstructorUsedError;
  double get totalHours => throw _privateConstructorUsedError;

  /// Create a copy of AvailableDate
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $AvailableDateCopyWith<AvailableDate> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $AvailableDateCopyWith<$Res> {
  factory $AvailableDateCopyWith(
    AvailableDate value,
    $Res Function(AvailableDate) then,
  ) = _$AvailableDateCopyWithImpl<$Res, AvailableDate>;
  @useResult
  $Res call({DateTime date, int segmentCount, double totalHours});
}

/// @nodoc
class _$AvailableDateCopyWithImpl<$Res, $Val extends AvailableDate>
    implements $AvailableDateCopyWith<$Res> {
  _$AvailableDateCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of AvailableDate
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? date = null,
    Object? segmentCount = null,
    Object? totalHours = null,
  }) {
    return _then(
      _value.copyWith(
            date:
                null == date
                    ? _value.date
                    : date // ignore: cast_nullable_to_non_nullable
                        as DateTime,
            segmentCount:
                null == segmentCount
                    ? _value.segmentCount
                    : segmentCount // ignore: cast_nullable_to_non_nullable
                        as int,
            totalHours:
                null == totalHours
                    ? _value.totalHours
                    : totalHours // ignore: cast_nullable_to_non_nullable
                        as double,
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$AvailableDateImplCopyWith<$Res>
    implements $AvailableDateCopyWith<$Res> {
  factory _$$AvailableDateImplCopyWith(
    _$AvailableDateImpl value,
    $Res Function(_$AvailableDateImpl) then,
  ) = __$$AvailableDateImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({DateTime date, int segmentCount, double totalHours});
}

/// @nodoc
class __$$AvailableDateImplCopyWithImpl<$Res>
    extends _$AvailableDateCopyWithImpl<$Res, _$AvailableDateImpl>
    implements _$$AvailableDateImplCopyWith<$Res> {
  __$$AvailableDateImplCopyWithImpl(
    _$AvailableDateImpl _value,
    $Res Function(_$AvailableDateImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of AvailableDate
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? date = null,
    Object? segmentCount = null,
    Object? totalHours = null,
  }) {
    return _then(
      _$AvailableDateImpl(
        date:
            null == date
                ? _value.date
                : date // ignore: cast_nullable_to_non_nullable
                    as DateTime,
        segmentCount:
            null == segmentCount
                ? _value.segmentCount
                : segmentCount // ignore: cast_nullable_to_non_nullable
                    as int,
        totalHours:
            null == totalHours
                ? _value.totalHours
                : totalHours // ignore: cast_nullable_to_non_nullable
                    as double,
      ),
    );
  }
}

/// @nodoc

class _$AvailableDateImpl implements _AvailableDate {
  const _$AvailableDateImpl({
    required this.date,
    this.segmentCount = 0,
    this.totalHours = 0.0,
  });

  @override
  final DateTime date;
  @override
  @JsonKey()
  final int segmentCount;
  @override
  @JsonKey()
  final double totalHours;

  @override
  String toString() {
    return 'AvailableDate(date: $date, segmentCount: $segmentCount, totalHours: $totalHours)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$AvailableDateImpl &&
            (identical(other.date, date) || other.date == date) &&
            (identical(other.segmentCount, segmentCount) ||
                other.segmentCount == segmentCount) &&
            (identical(other.totalHours, totalHours) ||
                other.totalHours == totalHours));
  }

  @override
  int get hashCode => Object.hash(runtimeType, date, segmentCount, totalHours);

  /// Create a copy of AvailableDate
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$AvailableDateImplCopyWith<_$AvailableDateImpl> get copyWith =>
      __$$AvailableDateImplCopyWithImpl<_$AvailableDateImpl>(this, _$identity);
}

abstract class _AvailableDate implements AvailableDate {
  const factory _AvailableDate({
    required final DateTime date,
    final int segmentCount,
    final double totalHours,
  }) = _$AvailableDateImpl;

  @override
  DateTime get date;
  @override
  int get segmentCount;
  @override
  double get totalHours;

  /// Create a copy of AvailableDate
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$AvailableDateImplCopyWith<_$AvailableDateImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
