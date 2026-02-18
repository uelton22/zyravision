// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'resource_quota.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
  'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models',
);

/// @nodoc
mixin _$ResourceQuota {
  int get used => throw _privateConstructorUsedError;
  int get limit => throw _privateConstructorUsedError;

  /// Create a copy of ResourceQuota
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $ResourceQuotaCopyWith<ResourceQuota> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $ResourceQuotaCopyWith<$Res> {
  factory $ResourceQuotaCopyWith(
    ResourceQuota value,
    $Res Function(ResourceQuota) then,
  ) = _$ResourceQuotaCopyWithImpl<$Res, ResourceQuota>;
  @useResult
  $Res call({int used, int limit});
}

/// @nodoc
class _$ResourceQuotaCopyWithImpl<$Res, $Val extends ResourceQuota>
    implements $ResourceQuotaCopyWith<$Res> {
  _$ResourceQuotaCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of ResourceQuota
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({Object? used = null, Object? limit = null}) {
    return _then(
      _value.copyWith(
            used:
                null == used
                    ? _value.used
                    : used // ignore: cast_nullable_to_non_nullable
                        as int,
            limit:
                null == limit
                    ? _value.limit
                    : limit // ignore: cast_nullable_to_non_nullable
                        as int,
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$ResourceQuotaImplCopyWith<$Res>
    implements $ResourceQuotaCopyWith<$Res> {
  factory _$$ResourceQuotaImplCopyWith(
    _$ResourceQuotaImpl value,
    $Res Function(_$ResourceQuotaImpl) then,
  ) = __$$ResourceQuotaImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({int used, int limit});
}

/// @nodoc
class __$$ResourceQuotaImplCopyWithImpl<$Res>
    extends _$ResourceQuotaCopyWithImpl<$Res, _$ResourceQuotaImpl>
    implements _$$ResourceQuotaImplCopyWith<$Res> {
  __$$ResourceQuotaImplCopyWithImpl(
    _$ResourceQuotaImpl _value,
    $Res Function(_$ResourceQuotaImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of ResourceQuota
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({Object? used = null, Object? limit = null}) {
    return _then(
      _$ResourceQuotaImpl(
        used:
            null == used
                ? _value.used
                : used // ignore: cast_nullable_to_non_nullable
                    as int,
        limit:
            null == limit
                ? _value.limit
                : limit // ignore: cast_nullable_to_non_nullable
                    as int,
      ),
    );
  }
}

/// @nodoc

class _$ResourceQuotaImpl extends _ResourceQuota {
  const _$ResourceQuotaImpl({this.used = 0, this.limit = 0}) : super._();

  @override
  @JsonKey()
  final int used;
  @override
  @JsonKey()
  final int limit;

  @override
  String toString() {
    return 'ResourceQuota(used: $used, limit: $limit)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$ResourceQuotaImpl &&
            (identical(other.used, used) || other.used == used) &&
            (identical(other.limit, limit) || other.limit == limit));
  }

  @override
  int get hashCode => Object.hash(runtimeType, used, limit);

  /// Create a copy of ResourceQuota
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$ResourceQuotaImplCopyWith<_$ResourceQuotaImpl> get copyWith =>
      __$$ResourceQuotaImplCopyWithImpl<_$ResourceQuotaImpl>(this, _$identity);
}

abstract class _ResourceQuota extends ResourceQuota {
  const factory _ResourceQuota({final int used, final int limit}) =
      _$ResourceQuotaImpl;
  const _ResourceQuota._() : super._();

  @override
  int get used;
  @override
  int get limit;

  /// Create a copy of ResourceQuota
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$ResourceQuotaImplCopyWith<_$ResourceQuotaImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
mixin _$StorageQuota {
  double get usedGb => throw _privateConstructorUsedError;
  double get limitGb => throw _privateConstructorUsedError;

  /// Create a copy of StorageQuota
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $StorageQuotaCopyWith<StorageQuota> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $StorageQuotaCopyWith<$Res> {
  factory $StorageQuotaCopyWith(
    StorageQuota value,
    $Res Function(StorageQuota) then,
  ) = _$StorageQuotaCopyWithImpl<$Res, StorageQuota>;
  @useResult
  $Res call({double usedGb, double limitGb});
}

/// @nodoc
class _$StorageQuotaCopyWithImpl<$Res, $Val extends StorageQuota>
    implements $StorageQuotaCopyWith<$Res> {
  _$StorageQuotaCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of StorageQuota
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({Object? usedGb = null, Object? limitGb = null}) {
    return _then(
      _value.copyWith(
            usedGb:
                null == usedGb
                    ? _value.usedGb
                    : usedGb // ignore: cast_nullable_to_non_nullable
                        as double,
            limitGb:
                null == limitGb
                    ? _value.limitGb
                    : limitGb // ignore: cast_nullable_to_non_nullable
                        as double,
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$StorageQuotaImplCopyWith<$Res>
    implements $StorageQuotaCopyWith<$Res> {
  factory _$$StorageQuotaImplCopyWith(
    _$StorageQuotaImpl value,
    $Res Function(_$StorageQuotaImpl) then,
  ) = __$$StorageQuotaImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({double usedGb, double limitGb});
}

/// @nodoc
class __$$StorageQuotaImplCopyWithImpl<$Res>
    extends _$StorageQuotaCopyWithImpl<$Res, _$StorageQuotaImpl>
    implements _$$StorageQuotaImplCopyWith<$Res> {
  __$$StorageQuotaImplCopyWithImpl(
    _$StorageQuotaImpl _value,
    $Res Function(_$StorageQuotaImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of StorageQuota
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({Object? usedGb = null, Object? limitGb = null}) {
    return _then(
      _$StorageQuotaImpl(
        usedGb:
            null == usedGb
                ? _value.usedGb
                : usedGb // ignore: cast_nullable_to_non_nullable
                    as double,
        limitGb:
            null == limitGb
                ? _value.limitGb
                : limitGb // ignore: cast_nullable_to_non_nullable
                    as double,
      ),
    );
  }
}

/// @nodoc

class _$StorageQuotaImpl extends _StorageQuota {
  const _$StorageQuotaImpl({this.usedGb = 0.0, this.limitGb = 0.0}) : super._();

  @override
  @JsonKey()
  final double usedGb;
  @override
  @JsonKey()
  final double limitGb;

  @override
  String toString() {
    return 'StorageQuota(usedGb: $usedGb, limitGb: $limitGb)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$StorageQuotaImpl &&
            (identical(other.usedGb, usedGb) || other.usedGb == usedGb) &&
            (identical(other.limitGb, limitGb) || other.limitGb == limitGb));
  }

  @override
  int get hashCode => Object.hash(runtimeType, usedGb, limitGb);

  /// Create a copy of StorageQuota
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$StorageQuotaImplCopyWith<_$StorageQuotaImpl> get copyWith =>
      __$$StorageQuotaImplCopyWithImpl<_$StorageQuotaImpl>(this, _$identity);
}

abstract class _StorageQuota extends StorageQuota {
  const factory _StorageQuota({final double usedGb, final double limitGb}) =
      _$StorageQuotaImpl;
  const _StorageQuota._() : super._();

  @override
  double get usedGb;
  @override
  double get limitGb;

  /// Create a copy of StorageQuota
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$StorageQuotaImplCopyWith<_$StorageQuotaImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
mixin _$DashboardQuotas {
  ResourceQuota get cameras => throw _privateConstructorUsedError;
  ResourceQuota get users => throw _privateConstructorUsedError;
  StorageQuota get storage => throw _privateConstructorUsedError;
  ResourceQuota get streams => throw _privateConstructorUsedError;

  /// Create a copy of DashboardQuotas
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $DashboardQuotasCopyWith<DashboardQuotas> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $DashboardQuotasCopyWith<$Res> {
  factory $DashboardQuotasCopyWith(
    DashboardQuotas value,
    $Res Function(DashboardQuotas) then,
  ) = _$DashboardQuotasCopyWithImpl<$Res, DashboardQuotas>;
  @useResult
  $Res call({
    ResourceQuota cameras,
    ResourceQuota users,
    StorageQuota storage,
    ResourceQuota streams,
  });

  $ResourceQuotaCopyWith<$Res> get cameras;
  $ResourceQuotaCopyWith<$Res> get users;
  $StorageQuotaCopyWith<$Res> get storage;
  $ResourceQuotaCopyWith<$Res> get streams;
}

/// @nodoc
class _$DashboardQuotasCopyWithImpl<$Res, $Val extends DashboardQuotas>
    implements $DashboardQuotasCopyWith<$Res> {
  _$DashboardQuotasCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of DashboardQuotas
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? cameras = null,
    Object? users = null,
    Object? storage = null,
    Object? streams = null,
  }) {
    return _then(
      _value.copyWith(
            cameras:
                null == cameras
                    ? _value.cameras
                    : cameras // ignore: cast_nullable_to_non_nullable
                        as ResourceQuota,
            users:
                null == users
                    ? _value.users
                    : users // ignore: cast_nullable_to_non_nullable
                        as ResourceQuota,
            storage:
                null == storage
                    ? _value.storage
                    : storage // ignore: cast_nullable_to_non_nullable
                        as StorageQuota,
            streams:
                null == streams
                    ? _value.streams
                    : streams // ignore: cast_nullable_to_non_nullable
                        as ResourceQuota,
          )
          as $Val,
    );
  }

  /// Create a copy of DashboardQuotas
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $ResourceQuotaCopyWith<$Res> get cameras {
    return $ResourceQuotaCopyWith<$Res>(_value.cameras, (value) {
      return _then(_value.copyWith(cameras: value) as $Val);
    });
  }

  /// Create a copy of DashboardQuotas
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $ResourceQuotaCopyWith<$Res> get users {
    return $ResourceQuotaCopyWith<$Res>(_value.users, (value) {
      return _then(_value.copyWith(users: value) as $Val);
    });
  }

  /// Create a copy of DashboardQuotas
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $StorageQuotaCopyWith<$Res> get storage {
    return $StorageQuotaCopyWith<$Res>(_value.storage, (value) {
      return _then(_value.copyWith(storage: value) as $Val);
    });
  }

  /// Create a copy of DashboardQuotas
  /// with the given fields replaced by the non-null parameter values.
  @override
  @pragma('vm:prefer-inline')
  $ResourceQuotaCopyWith<$Res> get streams {
    return $ResourceQuotaCopyWith<$Res>(_value.streams, (value) {
      return _then(_value.copyWith(streams: value) as $Val);
    });
  }
}

/// @nodoc
abstract class _$$DashboardQuotasImplCopyWith<$Res>
    implements $DashboardQuotasCopyWith<$Res> {
  factory _$$DashboardQuotasImplCopyWith(
    _$DashboardQuotasImpl value,
    $Res Function(_$DashboardQuotasImpl) then,
  ) = __$$DashboardQuotasImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({
    ResourceQuota cameras,
    ResourceQuota users,
    StorageQuota storage,
    ResourceQuota streams,
  });

  @override
  $ResourceQuotaCopyWith<$Res> get cameras;
  @override
  $ResourceQuotaCopyWith<$Res> get users;
  @override
  $StorageQuotaCopyWith<$Res> get storage;
  @override
  $ResourceQuotaCopyWith<$Res> get streams;
}

/// @nodoc
class __$$DashboardQuotasImplCopyWithImpl<$Res>
    extends _$DashboardQuotasCopyWithImpl<$Res, _$DashboardQuotasImpl>
    implements _$$DashboardQuotasImplCopyWith<$Res> {
  __$$DashboardQuotasImplCopyWithImpl(
    _$DashboardQuotasImpl _value,
    $Res Function(_$DashboardQuotasImpl) _then,
  ) : super(_value, _then);

  /// Create a copy of DashboardQuotas
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? cameras = null,
    Object? users = null,
    Object? storage = null,
    Object? streams = null,
  }) {
    return _then(
      _$DashboardQuotasImpl(
        cameras:
            null == cameras
                ? _value.cameras
                : cameras // ignore: cast_nullable_to_non_nullable
                    as ResourceQuota,
        users:
            null == users
                ? _value.users
                : users // ignore: cast_nullable_to_non_nullable
                    as ResourceQuota,
        storage:
            null == storage
                ? _value.storage
                : storage // ignore: cast_nullable_to_non_nullable
                    as StorageQuota,
        streams:
            null == streams
                ? _value.streams
                : streams // ignore: cast_nullable_to_non_nullable
                    as ResourceQuota,
      ),
    );
  }
}

/// @nodoc

class _$DashboardQuotasImpl implements _DashboardQuotas {
  const _$DashboardQuotasImpl({
    this.cameras = const ResourceQuota(),
    this.users = const ResourceQuota(),
    this.storage = const StorageQuota(),
    this.streams = const ResourceQuota(),
  });

  @override
  @JsonKey()
  final ResourceQuota cameras;
  @override
  @JsonKey()
  final ResourceQuota users;
  @override
  @JsonKey()
  final StorageQuota storage;
  @override
  @JsonKey()
  final ResourceQuota streams;

  @override
  String toString() {
    return 'DashboardQuotas(cameras: $cameras, users: $users, storage: $storage, streams: $streams)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$DashboardQuotasImpl &&
            (identical(other.cameras, cameras) || other.cameras == cameras) &&
            (identical(other.users, users) || other.users == users) &&
            (identical(other.storage, storage) || other.storage == storage) &&
            (identical(other.streams, streams) || other.streams == streams));
  }

  @override
  int get hashCode =>
      Object.hash(runtimeType, cameras, users, storage, streams);

  /// Create a copy of DashboardQuotas
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$DashboardQuotasImplCopyWith<_$DashboardQuotasImpl> get copyWith =>
      __$$DashboardQuotasImplCopyWithImpl<_$DashboardQuotasImpl>(
        this,
        _$identity,
      );
}

abstract class _DashboardQuotas implements DashboardQuotas {
  const factory _DashboardQuotas({
    final ResourceQuota cameras,
    final ResourceQuota users,
    final StorageQuota storage,
    final ResourceQuota streams,
  }) = _$DashboardQuotasImpl;

  @override
  ResourceQuota get cameras;
  @override
  ResourceQuota get users;
  @override
  StorageQuota get storage;
  @override
  ResourceQuota get streams;

  /// Create a copy of DashboardQuotas
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$DashboardQuotasImplCopyWith<_$DashboardQuotasImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
