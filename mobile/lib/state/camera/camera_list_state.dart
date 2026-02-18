import 'package:freezed_annotation/freezed_annotation.dart';

import '../../domain/entities/camera.dart';

part 'camera_list_state.freezed.dart';

enum CameraViewMode { grid, list }

@freezed
class CameraListState with _$CameraListState {
  const factory CameraListState({
    @Default([]) List<Camera> cameras,
    @Default(false) bool isLoading,
    @Default(false) bool isLoadingMore,
    @Default(1) int currentPage,
    @Default(1) int lastPage,
    @Default(0) int total,
    @Default('') String searchQuery,
    String? statusFilter,
    @Default(CameraViewMode.grid) CameraViewMode viewMode,
    String? errorMessage,
  }) = _CameraListState;

  const CameraListState._();

  bool get hasMore => currentPage < lastPage;
  bool get hasError => errorMessage != null;
  bool get isEmpty => cameras.isEmpty && !isLoading;
  bool get hasFilters => searchQuery.isNotEmpty || statusFilter != null;
}
