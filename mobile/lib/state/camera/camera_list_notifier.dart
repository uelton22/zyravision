import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/usecases/camera/get_cameras_usecase.dart';
import 'camera_list_state.dart';
import 'camera_providers.dart';

class CameraListNotifier extends AutoDisposeNotifier<CameraListState> {
  late final GetCamerasUseCase _getCameras;

  @override
  CameraListState build() {
    _getCameras = ref.read(getCamerasUseCaseProvider);
    // Defer para o proximo microtask — state so esta disponivel apos build() retornar
    Future.microtask(() => _loadCameras());
    return const CameraListState(isLoading: true);
  }

  Future<void> _loadCameras({bool append = false}) async {
    try {
      if (!append) {
        state = state.copyWith(isLoading: true, errorMessage: null);
      } else {
        state = state.copyWith(isLoadingMore: true);
      }

      final page = append ? state.currentPage + 1 : 1;

      final result = await _getCameras(
        page: page,
        search: state.searchQuery.isNotEmpty ? state.searchQuery : null,
        status: state.statusFilter,
      );

      final cameras =
          append ? [...state.cameras, ...result.data] : result.data;

      state = state.copyWith(
        cameras: cameras,
        isLoading: false,
        isLoadingMore: false,
        currentPage: result.currentPage,
        lastPage: result.lastPage,
        total: result.total,
        errorMessage: null,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        isLoadingMore: false,
        errorMessage: e.toString(),
      );
    }
  }

  Future<void> refresh() async {
    state = state.copyWith(currentPage: 1);
    await _loadCameras();
  }

  Future<void> loadMore() async {
    if (state.isLoadingMore || !state.hasMore) return;
    await _loadCameras(append: true);
  }

  void setSearch(String query) {
    state = state.copyWith(searchQuery: query, currentPage: 1);
    _loadCameras();
  }

  void setStatusFilter(String? status) {
    state = state.copyWith(statusFilter: status, currentPage: 1);
    _loadCameras();
  }

  void toggleViewMode() {
    state = state.copyWith(
      viewMode: state.viewMode == CameraViewMode.grid
          ? CameraViewMode.list
          : CameraViewMode.grid,
    );
  }

  void clearFilters() {
    state = state.copyWith(
      searchQuery: '',
      statusFilter: null,
      currentPage: 1,
    );
    _loadCameras();
  }
}
