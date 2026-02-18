// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'paginated_result.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
  'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models',
);

/// @nodoc
mixin _$PaginatedResult<T> {
  List<T> get data => throw _privateConstructorUsedError;
  int get currentPage => throw _privateConstructorUsedError;
  int get lastPage => throw _privateConstructorUsedError;
  int get perPage => throw _privateConstructorUsedError;
  int get total => throw _privateConstructorUsedError;

  /// Create a copy of PaginatedResult
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $PaginatedResultCopyWith<T, PaginatedResult<T>> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $PaginatedResultCopyWith<T, $Res> {
  factory $PaginatedResultCopyWith(
    PaginatedResult<T> value,
    $Res Function(PaginatedResult<T>) then,
  ) = _$PaginatedResultCopyWithImpl<T, $Res, PaginatedResult<T>>;
  @useResult
  $Res call({
    List<T> data,
    int currentPage,
    int lastPage,
    int perPage,
    int total,
  });
}

/// @nodoc
class _$PaginatedResultCopyWithImpl<T, $Res, $Val extends PaginatedResult<T>>
    implements $PaginatedResultCopyWith<T, $Res> {
  _$PaginatedResultCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of PaginatedResult
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? data = null,
    Object? currentPage = null,
    Object? lastPage = null,
    Object? perPage = null,
    Object? total = null,
  }) {
    return _then(
      _value.copyWith(
            data:
                null == data
                    ? _value.data
                    : data // ignore: cast_nullable_to_non_nullable
                        as List<T>,
            currentPage:
                null == currentPage
                    ? _value.currentPage
                    : currentPage // ignore: cast_nullable_to_non_nullable
                        as int,
            lastPage:
                null == lastPage
                    ? _value.lastPage
                    : lastPage // ignore: cast_nullable_to_non_nullable
                        as int,
            perPage:
                null == perPage
                    ? _value.perPage
                    : perPage // ignore: cast_nullable_to_non_nullable
                        as int,
            total:
                null == total
                    ? _value.total
                    : total // ignore: cast_nullable_to_non_nullable
                        as int,
          )
          as $Val,
    );
  }
}

/// @nodoc
abstract class _$$PaginatedResultImplCopyWith<T, $Res>
    implements $PaginatedResultCopyWith<T, $Res> {
  factory _$$PaginatedResultImplCopyWith(
    _$PaginatedResultImpl<T> value,
    $Res Function(_$PaginatedResultImpl<T>) then,
  ) = __$$PaginatedResultImplCopyWithImpl<T, $Res>;
  @override
  @useResult
  $Res call({
    List<T> data,
    int currentPage,
    int lastPage,
    int perPage,
    int total,
  });
}

/// @nodoc
class __$$PaginatedResultImplCopyWithImpl<T, $Res>
    extends _$PaginatedResultCopyWithImpl<T, $Res, _$PaginatedResultImpl<T>>
    implements _$$PaginatedResultImplCopyWith<T, $Res> {
  __$$PaginatedResultImplCopyWithImpl(
    _$PaginatedResultImpl<T> _value,
    $Res Function(_$PaginatedResultImpl<T>) _then,
  ) : super(_value, _then);

  /// Create a copy of PaginatedResult
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? data = null,
    Object? currentPage = null,
    Object? lastPage = null,
    Object? perPage = null,
    Object? total = null,
  }) {
    return _then(
      _$PaginatedResultImpl<T>(
        data:
            null == data
                ? _value._data
                : data // ignore: cast_nullable_to_non_nullable
                    as List<T>,
        currentPage:
            null == currentPage
                ? _value.currentPage
                : currentPage // ignore: cast_nullable_to_non_nullable
                    as int,
        lastPage:
            null == lastPage
                ? _value.lastPage
                : lastPage // ignore: cast_nullable_to_non_nullable
                    as int,
        perPage:
            null == perPage
                ? _value.perPage
                : perPage // ignore: cast_nullable_to_non_nullable
                    as int,
        total:
            null == total
                ? _value.total
                : total // ignore: cast_nullable_to_non_nullable
                    as int,
      ),
    );
  }
}

/// @nodoc

class _$PaginatedResultImpl<T> extends _PaginatedResult<T> {
  const _$PaginatedResultImpl({
    required final List<T> data,
    this.currentPage = 1,
    this.lastPage = 1,
    this.perPage = 15,
    this.total = 0,
  }) : _data = data,
       super._();

  final List<T> _data;
  @override
  List<T> get data {
    if (_data is EqualUnmodifiableListView) return _data;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_data);
  }

  @override
  @JsonKey()
  final int currentPage;
  @override
  @JsonKey()
  final int lastPage;
  @override
  @JsonKey()
  final int perPage;
  @override
  @JsonKey()
  final int total;

  @override
  String toString() {
    return 'PaginatedResult<$T>(data: $data, currentPage: $currentPage, lastPage: $lastPage, perPage: $perPage, total: $total)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$PaginatedResultImpl<T> &&
            const DeepCollectionEquality().equals(other._data, _data) &&
            (identical(other.currentPage, currentPage) ||
                other.currentPage == currentPage) &&
            (identical(other.lastPage, lastPage) ||
                other.lastPage == lastPage) &&
            (identical(other.perPage, perPage) || other.perPage == perPage) &&
            (identical(other.total, total) || other.total == total));
  }

  @override
  int get hashCode => Object.hash(
    runtimeType,
    const DeepCollectionEquality().hash(_data),
    currentPage,
    lastPage,
    perPage,
    total,
  );

  /// Create a copy of PaginatedResult
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$PaginatedResultImplCopyWith<T, _$PaginatedResultImpl<T>> get copyWith =>
      __$$PaginatedResultImplCopyWithImpl<T, _$PaginatedResultImpl<T>>(
        this,
        _$identity,
      );
}

abstract class _PaginatedResult<T> extends PaginatedResult<T> {
  const factory _PaginatedResult({
    required final List<T> data,
    final int currentPage,
    final int lastPage,
    final int perPage,
    final int total,
  }) = _$PaginatedResultImpl<T>;
  const _PaginatedResult._() : super._();

  @override
  List<T> get data;
  @override
  int get currentPage;
  @override
  int get lastPage;
  @override
  int get perPage;
  @override
  int get total;

  /// Create a copy of PaginatedResult
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$PaginatedResultImplCopyWith<T, _$PaginatedResultImpl<T>> get copyWith =>
      throw _privateConstructorUsedError;
}
