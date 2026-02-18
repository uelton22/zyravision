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
  String get role => throw _privateConstructorUsedError;
  DashboardStats get stats => throw _privateConstructorUsedError;
  List<CameraSummary> get cameras => throw _privateConstructorUsedError;
  List<AnalyticsEventSummary> get recentEvents =>
      throw _privateConstructorUsedError;
  DateTime? get syncedAt =>
      throw _privateConstructorUsedError; // Tenant-only fields
  DashboardQuotas? get quotas => throw _privateConstructorUsedError;
  PlanInfo? get plan => throw _privateConstructorUsedError; // Admin-only fields
  SystemHealth? get systemHealth => throw _privateConstructorUsedError;
  WorkersInfo? get workers => throw _privateConstructorUsedError;

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
    String role,
    DashboardStats stats,
    List<CameraSummary> cameras,
    List<AnalyticsEventSummary> recentEvents,
    DateTime? syncedAt,
    DashboardQuotas? quotas,
    PlanInfo? plan,
    SystemHealth? systemHealth,
    WorkersInfo? workers,
  });

  $DashboardStatsCopyWith<$Res> get stats;
  $DashboardQuotasCopyWith<$Res>? get quotas;
  $PlanInfoCopyWith<$Res>? get plan;
  $SystemHealthCopyWith<$Res>? get systemHealth;
  $WorkersInfoCopyWith<$Res>? get workers;
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
    Object? role = null,
    Object? stats = null,
    Object? cameras = null,
    Object? recentEvents = null,
    Object? syncedAt = freezed,
    Object? quotas = freezed,
    Object? plan = freezed,
    Object? systemHealth = freezed,
    Object? workers = freezed,
  }) {
    return _then(
      _value.copyWith(
            role:
                null == role
                    ? _value.role
                    : role // ignore: cast_nullable_to_non_nullable
                        as String,
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
            quotas:
                freezed == quotas
                    ? _value.quotas
                    : quotas // ignore: cast_nullable_to_non_nullable
                        as DashboardQuotas?,
            plan:
                freezed == plan
                    ? _value.plan
                    : plan // ignore: cast_nullable_to_non_nullable
                        as PlanInfo?,
            systemHealth:
                freezed == systemHealth
                    ? _value.systemHealth
                    : systemHealth // ignore: cast_nullable_to_non_nullable
                        as SystemHealth?,
            workers:
                freezed == workers
                    ? _value.workers
                    : workers // ignore: cast_nullable_to_non_nullable
                        as WorkersInfo?,
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

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $DashboardQuotasCopyWith<$Res>? get quotas {
    if (_value.quotas == null) {
      return null;
    }

    return $DashboardQuotasCopyWith<$Res>(_value.quotas!, (value) {
      return _then(_value.copyWith(quotas: value) as $Val);
    });
  }

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $PlanInfoCopyWith<$Res>? get plan {
    if (_value.plan == null) {
      return null;
    }

    return $PlanInfoCopyWith<$Res>(_value.plan!, (value) {
      return _then(_value.copyWith(plan: value) as $Val);
    });
  }

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $SystemHealthCopyWith<$Res>? get systemHealth {
    if (_value.systemHealth == null) {
      return null;
    }

    return $SystemHealthCopyWith<$Res>(_value.systemHealth!, (value) {
      return _then(_value.copyWith(systemHealth: value) as $Val);
    });
  }

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $WorkersInfoCopyWith<$Res>? get workers {
    if (_value.workers == null) {
      return null;
    }

    return $WorkersInfoCopyWith<$Res>(_value.workers!, (value) {
      return _then(_value.copyWith(workers: value) as $Val);
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
    String role,
    DashboardStats stats,
    List<CameraSummary> cameras,
    List<AnalyticsEventSummary> recentEvents,
    DateTime? syncedAt,
    DashboardQuotas? quotas,
    PlanInfo? plan,
    SystemHealth? systemHealth,
    WorkersInfo? workers,
  });

  @override
  $DashboardStatsCopyWith<$Res> get stats;
  @override
  $DashboardQuotasCopyWith<$Res>? get quotas;
  @override
  $PlanInfoCopyWith<$Res>? get plan;
  @override
  $SystemHealthCopyWith<$Res>? get systemHealth;
  @override
  $WorkersInfoCopyWith<$Res>? get workers;
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
    Object? role = null,
    Object? stats = null,
    Object? cameras = null,
    Object? recentEvents = null,
    Object? syncedAt = freezed,
    Object? quotas = freezed,
    Object? plan = freezed,
    Object? systemHealth = freezed,
    Object? workers = freezed,
  }) {
    return _then(
      _$DashboardDataImpl(
        role:
            null == role
                ? _value.role
                : role // ignore: cast_nullable_to_non_nullable
                    as String,
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
        quotas:
            freezed == quotas
                ? _value.quotas
                : quotas // ignore: cast_nullable_to_non_nullable
                    as DashboardQuotas?,
        plan:
            freezed == plan
                ? _value.plan
                : plan // ignore: cast_nullable_to_non_nullable
                    as PlanInfo?,
        systemHealth:
            freezed == systemHealth
                ? _value.systemHealth
                : systemHealth // ignore: cast_nullable_to_non_nullable
                    as SystemHealth?,
        workers:
            freezed == workers
                ? _value.workers
                : workers // ignore: cast_nullable_to_non_nullable
                    as WorkersInfo?,
      ),
    );
  }
}

/// @nodoc

class _$DashboardDataImpl extends _DashboardData {
  const _$DashboardDataImpl({
    required this.role,
    required this.stats,
    final List<CameraSummary> cameras = const [],
    final List<AnalyticsEventSummary> recentEvents = const [],
    this.syncedAt,
    this.quotas,
    this.plan,
    this.systemHealth,
    this.workers,
  }) : _cameras = cameras,
       _recentEvents = recentEvents,
       super._();

  @override
  final String role;
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
  // Tenant-only fields
  @override
  final DashboardQuotas? quotas;
  @override
  final PlanInfo? plan;
  // Admin-only fields
  @override
  final SystemHealth? systemHealth;
  @override
  final WorkersInfo? workers;

  @override
  String toString() {
    return 'DashboardData(role: $role, stats: $stats, cameras: $cameras, recentEvents: $recentEvents, syncedAt: $syncedAt, quotas: $quotas, plan: $plan, systemHealth: $systemHealth, workers: $workers)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$DashboardDataImpl &&
            (identical(other.role, role) || other.role == role) &&
            (identical(other.stats, stats) || other.stats == stats) &&
            const DeepCollectionEquality().equals(other._cameras, _cameras) &&
            const DeepCollectionEquality().equals(
              other._recentEvents,
              _recentEvents,
            ) &&
            (identical(other.syncedAt, syncedAt) ||
                other.syncedAt == syncedAt) &&
            (identical(other.quotas, quotas) || other.quotas == quotas) &&
            (identical(other.plan, plan) || other.plan == plan) &&
            (identical(other.systemHealth, systemHealth) ||
                other.systemHealth == systemHealth) &&
            (identical(other.workers, workers) || other.workers == workers));
  }

  @override
  int get hashCode => Object.hash(
    runtimeType,
    role,
    stats,
    const DeepCollectionEquality().hash(_cameras),
    const DeepCollectionEquality().hash(_recentEvents),
    syncedAt,
    quotas,
    plan,
    systemHealth,
    workers,
  );

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$DashboardDataImplCopyWith<_$DashboardDataImpl> get copyWith =>
      __$$DashboardDataImplCopyWithImpl<_$DashboardDataImpl>(this, _$identity);
}

abstract class _DashboardData extends DashboardData {
  const factory _DashboardData({
    required final String role,
    required final DashboardStats stats,
    final List<CameraSummary> cameras,
    final List<AnalyticsEventSummary> recentEvents,
    final DateTime? syncedAt,
    final DashboardQuotas? quotas,
    final PlanInfo? plan,
    final SystemHealth? systemHealth,
    final WorkersInfo? workers,
  }) = _$DashboardDataImpl;
  const _DashboardData._() : super._();

  @override
  String get role;
  @override
  DashboardStats get stats;
  @override
  List<CameraSummary> get cameras;
  @override
  List<AnalyticsEventSummary> get recentEvents;
  @override
  DateTime? get syncedAt; // Tenant-only fields
  @override
  DashboardQuotas? get quotas;
  @override
  PlanInfo? get plan; // Admin-only fields
  @override
  SystemHealth? get systemHealth;
  @override
  WorkersInfo? get workers;

  /// Create a copy of DashboardData
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$DashboardDataImplCopyWith<_$DashboardDataImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
