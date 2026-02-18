import 'package:freezed_annotation/freezed_annotation.dart';

part 'paginated_result.freezed.dart';

@freezed
class PaginatedResult<T> with _$PaginatedResult<T> {
  const factory PaginatedResult({
    required List<T> data,
    @Default(1) int currentPage,
    @Default(1) int lastPage,
    @Default(15) int perPage,
    @Default(0) int total,
  }) = _PaginatedResult<T>;

  const PaginatedResult._();

  bool get hasMore => currentPage < lastPage;
  int get nextPage => currentPage + 1;
}
