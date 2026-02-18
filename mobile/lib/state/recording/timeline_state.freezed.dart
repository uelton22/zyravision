// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'timeline_state.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
  'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models',
);

/// @nodoc
mixin _$TimelineState {
  bool get isLoading => throw _privateConstructorUsedError;
  TimelineData? get data => throw _privateConstructorUsedError;
  List<AvailableDate> get availableDates => throw _privateConstructorUsedError;
  DateTime? get selectedDate => throw _privateConstructorUsedError;
  DateTime? get currentPlaybackTime => throw _privateConstructorUsedError;
  String? get activeSegmentId => throw _privateConstructorUsedError;
  double get playbackSpeed => throw _privateConstructorUsedError;
  bool get isPlaying => throw _privateConstructorUsedError;
  String? get errorMessage => throw _privateConstructorUsedError;

  /// Create a copy of TimelineState
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $TimelineStateCopyWith<TimelineState> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $TimelineStateCopyWith<$Res> {
  factory $TimelineStateCopyWith(
    TimelineState value,
    $Res Function(TimelineState) then,
  ) = _$TimelineStateCopyWithImpl<$Res, TimelineState>;
  @useResult
  $Res call({
    bool isLoading,
    TimelineData? data,
    List<AvailableDate> availableDates,
    DateTime? selectedDate,
    DateTime? currentPlaybackTime,
    String? activeSegmentId,
    double playbackSpeed,
    bool isPlaying,
    String? errorMessage,
  });

  $TimelineDataCopyWith<$Res>? get data;
}

/// @nodoc
class _$TimelineStateCopyWithImpl<$Res, $Val extends TimelineState>
    implements $TimelineStateCopyWith<$Res> {
  _$TimelineStateCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of TimelineState
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? isLoading = null,
    Object? data = freezed,
    Object? availableDates = null,
    Object? selectedDate = freezed,
    Object? currentPlaybackTime = freezed,
    Object? activeSegmentId = freezed,
    Object? playbackSpeed = null,
    Object? isPlaying = null,
    Object? errorMessage = freezed,
  }) {
    return _then(
      _value.copyWith(
            isLoading:
                null == isLoading
                    ? _value.isLoading
                    : isLoading // ignore: cast_nullable_to_non_nullable
                        as bool,
            data:
                freezed == data
                    ? _value.data
                    : data // ignore: cast_nullable_to_non_nullable
                        as TimelineData?,
            availableDates:
                null == availableDates
                    ? _value.availableDates
                    : availableDates // ignore: cast_nullable_to_non_nullable
                        as List<AvailableDate>,
            selectedDate:
                freezed == selectedDate
                    ? _value.selectedDate
                    : selectedDate // ignore: cast_nullable_to_non_nullable
                        as DateTime?,
            currentPlaybackTime:
                freezed == currentPlaybackTime
                    ? _value.currentPlaybackTime
                    : currentPlaybackTime // ignore: cast_nullable_to_non_nullable
                        as DateTime?,
            activeSegmentId:
                freezed == activeSegmentId
                    ? _value.activeSegmentId
                    : activeSegmentId // ignore: cast_nullable_to_non_nullable
                        as String?,
            playbackSpeed:
                null == playbackSpeed
                    ? _value.playbackSpeed
                    : playbackSpeed // ignore: cast_nullable_to_non_nullable
                        as double,
            isPlaying:
                null == isPlaying
                    ? _value.isPlaying
                    : isPlaying // ignore: cast_nullable_to_non_nullable
                        as bool,
            errorMessage:
                freezed == errorMessage
                    ? _value.errorMessage
                    : errorMessage // ignore: cast_nullable_to_non_nullable
                        as String?,
          )
          as $Val,
    );
  }

  /// Create a copy of TimelineState
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $TimelineDataCopyWith<$Res>? get data {
    if (_value.data == null) {
      return null;
    }

    return $TimelineDataCopyWith<$Res>(_value.data!, (value) {
      return _then(_value.copyWith(data: value) as $Val);
    });
  }
}

/// @nodoc
abstract class _$$TimelineStateImplCopyWith<$Res>
    implements $TimelineStateCopyWith<$Res> {
  factory _$$TimelineStateImplCopyWith(
    _$TimelineStateImpl value,
    $Res Function(_$TimelineStateImpl) then,
  ) = __$$TimelineStateImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({
    bool isLoading,
    TimelineData? data,
    List<AvailableDate> availableDates,
    DateTime? selectedDate,
    DateTime? currentPlaybackTime,
    String? activeSegmentId,
    double playbackSpeed,
    bool isPlaying,
    String? errorMessage,
  });

  @override
  $TimelineDataCopyWith<$Res>? get data;
}

/// @nodoc
class __$$TimelineStateImplCopyWithImpl<$Res>
    extends _$TimelineStateCopyWithImpl<$Res, _$TimelineStateImpl>
    implements _$$TimelineStateImplCopyWith<$Res> {
  __$$TimelineStateImplCopyWithImpl(
    _$TimelineStateImpl _value,
    $Res Function(_$TimelineStateImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of TimelineState
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? isLoading = null,
    Object? data = freezed,
    Object? availableDates = null,
    Object? selectedDate = freezed,
    Object? currentPlaybackTime = freezed,
    Object? activeSegmentId = freezed,
    Object? playbackSpeed = null,
    Object? isPlaying = null,
    Object? errorMessage = freezed,
  }) {
    return _then(
      _$TimelineStateImpl(
        isLoading:
            null == isLoading
                ? _value.isLoading
                : isLoading // ignore: cast_nullable_to_non_nullable
                    as bool,
        data:
            freezed == data
                ? _value.data
                : data // ignore: cast_nullable_to_non_nullable
                    as TimelineData?,
        availableDates:
            null == availableDates
                ? _value._availableDates
                : availableDates // ignore: cast_nullable_to_non_nullable
                    as List<AvailableDate>,
        selectedDate:
            freezed == selectedDate
                ? _value.selectedDate
                : selectedDate // ignore: cast_nullable_to_non_nullable
                    as DateTime?,
        currentPlaybackTime:
            freezed == currentPlaybackTime
                ? _value.currentPlaybackTime
                : currentPlaybackTime // ignore: cast_nullable_to_non_nullable
                    as DateTime?,
        activeSegmentId:
            freezed == activeSegmentId
                ? _value.activeSegmentId
                : activeSegmentId // ignore: cast_nullable_to_non_nullable
                    as String?,
        playbackSpeed:
            null == playbackSpeed
                ? _value.playbackSpeed
                : playbackSpeed // ignore: cast_nullable_to_non_nullable
                    as double,
        isPlaying:
            null == isPlaying
                ? _value.isPlaying
                : isPlaying // ignore: cast_nullable_to_non_nullable
                    as bool,
        errorMessage:
            freezed == errorMessage
                ? _value.errorMessage
                : errorMessage // ignore: cast_nullable_to_non_nullable
                    as String?,
      ),
    );
  }
}

/// @nodoc

class _$TimelineStateImpl extends _TimelineState {
  const _$TimelineStateImpl({
    this.isLoading = false,
    this.data,
    final List<AvailableDate> availableDates = const [],
    this.selectedDate,
    this.currentPlaybackTime,
    this.activeSegmentId,
    this.playbackSpeed = 1.0,
    this.isPlaying = false,
    this.errorMessage,
  }) : _availableDates = availableDates,
       super._();

  @override
  @JsonKey()
  final bool isLoading;
  @override
  final TimelineData? data;
  final List<AvailableDate> _availableDates;
  @override
  @JsonKey()
  List<AvailableDate> get availableDates {
    if (_availableDates is EqualUnmodifiableListView) return _availableDates;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_availableDates);
  }

  @override
  final DateTime? selectedDate;
  @override
  final DateTime? currentPlaybackTime;
  @override
  final String? activeSegmentId;
  @override
  @JsonKey()
  final double playbackSpeed;
  @override
  @JsonKey()
  final bool isPlaying;
  @override
  final String? errorMessage;

  @override
  String toString() {
    return 'TimelineState(isLoading: $isLoading, data: $data, availableDates: $availableDates, selectedDate: $selectedDate, currentPlaybackTime: $currentPlaybackTime, activeSegmentId: $activeSegmentId, playbackSpeed: $playbackSpeed, isPlaying: $isPlaying, errorMessage: $errorMessage)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$TimelineStateImpl &&
            (identical(other.isLoading, isLoading) ||
                other.isLoading == isLoading) &&
            (identical(other.data, data) || other.data == data) &&
            const DeepCollectionEquality().equals(
              other._availableDates,
              _availableDates,
            ) &&
            (identical(other.selectedDate, selectedDate) ||
                other.selectedDate == selectedDate) &&
            (identical(other.currentPlaybackTime, currentPlaybackTime) ||
                other.currentPlaybackTime == currentPlaybackTime) &&
            (identical(other.activeSegmentId, activeSegmentId) ||
                other.activeSegmentId == activeSegmentId) &&
            (identical(other.playbackSpeed, playbackSpeed) ||
                other.playbackSpeed == playbackSpeed) &&
            (identical(other.isPlaying, isPlaying) ||
                other.isPlaying == isPlaying) &&
            (identical(other.errorMessage, errorMessage) ||
                other.errorMessage == errorMessage));
  }

  @override
  int get hashCode => Object.hash(
    runtimeType,
    isLoading,
    data,
    const DeepCollectionEquality().hash(_availableDates),
    selectedDate,
    currentPlaybackTime,
    activeSegmentId,
    playbackSpeed,
    isPlaying,
    errorMessage,
  );

  /// Create a copy of TimelineState
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$TimelineStateImplCopyWith<_$TimelineStateImpl> get copyWith =>
      __$$TimelineStateImplCopyWithImpl<_$TimelineStateImpl>(this, _$identity);
}

abstract class _TimelineState extends TimelineState {
  const factory _TimelineState({
    final bool isLoading,
    final TimelineData? data,
    final List<AvailableDate> availableDates,
    final DateTime? selectedDate,
    final DateTime? currentPlaybackTime,
    final String? activeSegmentId,
    final double playbackSpeed,
    final bool isPlaying,
    final String? errorMessage,
  }) = _$TimelineStateImpl;
  const _TimelineState._() : super._();

  @override
  bool get isLoading;
  @override
  TimelineData? get data;
  @override
  List<AvailableDate> get availableDates;
  @override
  DateTime? get selectedDate;
  @override
  DateTime? get currentPlaybackTime;
  @override
  String? get activeSegmentId;
  @override
  double get playbackSpeed;
  @override
  bool get isPlaying;
  @override
  String? get errorMessage;

  /// Create a copy of TimelineState
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$TimelineStateImplCopyWith<_$TimelineStateImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
