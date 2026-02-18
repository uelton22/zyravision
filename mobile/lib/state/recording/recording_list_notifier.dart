import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/repositories/recording_repository.dart';
import '../../data/repositories/recording_repository_impl.dart';
import 'recording_list_state.dart';

class RecordingListNotifier extends AutoDisposeNotifier<RecordingListState> {
  late final RecordingRepository _repository;

  @override
  RecordingListState build() {
    _repository = ref.read(recordingRepositoryProvider);
    Future.microtask(() => _load());
    return const RecordingListState(isLoading: true);
  }

  Future<void> _load() async {
    try {
      state = state.copyWith(isLoading: true, errorMessage: null);

      final cameras = await _repository.getCamerasWithRecordings();

      state = state.copyWith(
        cameras: cameras,
        isLoading: false,
        errorMessage: null,
      );

      debugPrint('[RecordingList] Loaded ${cameras.length} cameras with recordings');
    } catch (e) {
      debugPrint('[RecordingList] Error: $e');
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString(),
      );
    }
  }

  Future<void> refresh() async {
    await _load();
  }
}
