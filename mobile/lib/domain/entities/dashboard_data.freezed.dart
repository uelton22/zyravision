// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'dashboard_data.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
  'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models',
);

/// @nodoc
mixin _$DashboardData {
  DashboardStats get stats => throw _privateConstructorUsedError;
  List<CameraSummary> get cameras => throw _privateConstructorUsedError;
  List<AnalyticsEventSummary> get recentEvents =>
      throw _privateConstructorUsedError;
  DateTime? get syncedAt => throw _privateConstructorUsedError;

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $DashboardDataCopyWith<DashboardData> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $DashboardDataCopyWith<$Res> {
  factory $DashboardDataCopyWith(
    DashboardData value,
    $Res Function(DashboardData) then,
  ) = _$DashboardDataCopyWithImpl<$Res, DashboardData>;
  @useResult
  $Res call({
    DashboardStats stats,
    List<CameraSummary> cameras,
    List<AnalyticsEventSummary> recentEvents,
    DateTime? syncedAt,
  });

  $DashboardStatsCopyWith<$Res> get stats;
}

/// @nodoc
class _$DashboardDataCopyWithImpl<$Res, $Val extends DashboardData>
    implements $DashboardDataCopyWith<$Res> {
  _$DashboardDataCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? stats = null,
    Object? cameras = null,
    Object? recentEvents = null,
    Object? syncedAt = freezed,
  }) {
    return _then(
      _value.copyWith(
            stats:
                null == stats
                    ? _value.stats
                    : stats // ignore: cast_nullable_to_non_nullable
                        as DashboardStats,
            cameras:
                null == cameras
                    ? _value.cameras
                    : cameras // ignore: cast_nullable_to_non_nullable
                        as List<CameraSummary>,
            recentEvents:
                null == recentEvents
                    ? _value.recentEvents
                    : recentEvents // ignore: cast_nullable_to_non_nullable
                        as List<AnalyticsEventSummary>,
            syncedAt:
                freezed == syncedAt
                    ? _value.syncedAt
                    : syncedAt // ignore: cast_nullable_to_non_nullable
                        as DateTime?,
          )
          as $Val,
    );
  }

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $DashboardStatsCopyWith<$Res> get stats {
    return $DashboardStatsCopyWith<$Res>(_value.stats, (value) {
      return _then(_value.copyWith(stats: value) as $Val);
    });
  }
}

/// @nodoc
abstract class _$$DashboardDataImplCopyWith<$Res>
    implements $DashboardDataCopyWith<$Res> {
  factory _$$DashboardDataImplCopyWith(
    _$DashboardDataImpl value,
    $Res Function(_$DashboardDataImpl) then,
  ) = __$$DashboardDataImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({
    DashboardStats stats,
    List<CameraSummary> cameras,
    List<AnalyticsEventSummary> recentEvents,
    DateTime? syncedAt,
  });

  @override
  $DashboardStatsCopyWith<$Res> get stats;
}

/// @nodoc
class __$$DashboardDataImplCopyWithImpl<$Res>
    extends _$DashboardDataCopyWithImpl<$Res, _$DashboardDataImpl>
    implements _$$DashboardDataImplCopyWith<$Res> {
  __$$DashboardDataImplCopyWithImpl(
    _$DashboardDataImpl _value,
    $Res Function(_$DashboardDataImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? stats = null,
    Object? cameras = null,
    Object? recentEvents = null,
    Object? syncedAt = freezed,
  }) {
    return _then(
      _$DashboardDataImpl(
        stats:
            null == stats
                ? _value.stats
                : stats // ignore: cast_nullable_to_non_nullable
                    as DashboardStats,
        cameras:
            null == cameras
                ? _value._cameras
                : cameras // ignore: cast_nullable_to_non_nullable
                    as List<CameraSummary>,
        recentEvents:
            null == recentEvents
                ? _value._recentEvents
                : recentEvents // ignore: cast_nullable_to_non_nullable
                    as List<AnalyticsEventSummary>,
        syncedAt:
            freezed == syncedAt
                ? _value.syncedAt
                : syncedAt // ignore: cast_nullable_to_non_nullable
                    as DateTime?,
      ),
    );
  }
}

/// @nodoc

class _$DashboardDataImpl implements _DashboardData {
  const _$DashboardDataImpl({
    required this.stats,
    final List<CameraSummary> cameras = const [],
    final List<AnalyticsEventSummary> recentEvents = const [],
    this.syncedAt,
  }) : _cameras = cameras,
       _recentEvents = recentEvents;

  @override
  final DashboardStats stats;
  final List<CameraSummary> _cameras;
  @override
  @JsonKey()
  List<CameraSummary> get cameras {
    if (_cameras is EqualUnmodifiableListView) return _cameras;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_cameras);
  }

  final List<AnalyticsEventSummary> _recentEvents;
  @override
  @JsonKey()
  List<AnalyticsEventSummary> get recentEvents {
    if (_recentEvents is EqualUnmodifiableListView) return _recentEvents;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_recentEvents);
  }

  @override
  final DateTime? syncedAt;

  @override
  String toString() {
    return 'DashboardData(stats: $stats, cameras: $cameras, recentEvents: $recentEvents, syncedAt: $syncedAt)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$DashboardDataImpl &&
            (identical(other.stats, stats) || other.stats == stats) &&
            const DeepCollectionEquality().equals(other._cameras, _cameras) &&
            const DeepCollectionEquality().equals(
              other._recentEvents,
              _recentEvents,
            ) &&
            (identical(other.syncedAt, syncedAt) ||
                other.syncedAt == syncedAt));
  }

  @override
  int get hashCode => Object.hash(
    runtimeType,
    stats,
    const DeepCollectionEquality().hash(_cameras),
    const DeepCollectionEquality().hash(_recentEvents),
    syncedAt,
  );

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$DashboardDataImplCopyWith<_$DashboardDataImpl> get copyWith =>
      __$$DashboardDataImplCopyWithImpl<_$DashboardDataImpl>(this, _$identity);
}

abstract class _DashboardData implements DashboardData {
  const factory _DashboardData({
    required final DashboardStats stats,
    final List<CameraSummary> cameras,
    final List<AnalyticsEventSummary> recentEvents,
    final DateTime? syncedAt,
  }) = _$DashboardDataImpl;

  @override
  DashboardStats get stats;
  @override
  List<CameraSummary> get cameras;
  @override
  List<AnalyticsEventSummary> get recentEvents;
  @override
  DateTime? get syncedAt;

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$DashboardDataImplCopyWith<_$DashboardDataImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
