import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/repositories/recording_repository_impl.dart';
import '../../domain/usecases/recording/get_available_dates_usecase.dart';
import '../../domain/usecases/recording/get_timeline_usecase.dart';
import 'timeline_notifier.dart';
import 'timeline_state.dart';

// ─── Use Cases ─────────────────────────────────────────

final getTimelineUseCaseProvider = Provider<GetTimelineUseCase>((ref) {
  return GetTimelineUseCase(ref.read(recordingRepositoryProvider));
});

final getAvailableDatesUseCaseProvider =
    Provider<GetAvailableDatesUseCase>((ref) {
  return GetAvailableDatesUseCase(ref.read(recordingRepositoryProvider));
});

// ─── State Notifiers ───────────────────────────────────

final timelineProvider = AutoDisposeNotifierProvider.family<
    TimelineNotifier, TimelineState, String>(
  TimelineNotifier.new,
);
