import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/repositories/recording_repository_impl.dart';
import '../../domain/usecases/recording/create_clip_usecase.dart';
import '../../domain/usecases/recording/get_available_dates_usecase.dart';
import '../../domain/usecases/recording/get_playback_segment_usecase.dart';
import '../../domain/usecases/recording/get_recordings_usecase.dart';
import '../../domain/usecases/recording/get_timeline_usecase.dart';
import 'recording_list_notifier.dart';
import 'recording_list_state.dart';
import 'timeline_notifier.dart';
import 'timeline_state.dart';

// ─── Use Cases ─────────────────────────────────────────

final getRecordingsUseCaseProvider = Provider<GetRecordingsUseCase>((ref) {
  return GetRecordingsUseCase(ref.read(recordingRepositoryProvider));
});

final getTimelineUseCaseProvider = Provider<GetTimelineUseCase>((ref) {
  return GetTimelineUseCase(ref.read(recordingRepositoryProvider));
});

final getAvailableDatesUseCaseProvider =
    Provider<GetAvailableDatesUseCase>((ref) {
  return GetAvailableDatesUseCase(ref.read(recordingRepositoryProvider));
});

final getPlaybackSegmentUseCaseProvider =
    Provider<GetPlaybackSegmentUseCase>((ref) {
  return GetPlaybackSegmentUseCase(ref.read(recordingRepositoryProvider));
});

final createClipUseCaseProvider = Provider<CreateClipUseCase>((ref) {
  return CreateClipUseCase(ref.read(recordingRepositoryProvider));
});

// ─── State Notifiers ───────────────────────────────────

final recordingListProvider =
    AutoDisposeNotifierProvider<RecordingListNotifier, RecordingListState>(
  RecordingListNotifier.new,
);

final timelineProvider = AutoDisposeNotifierProvider.family<
    TimelineNotifier, TimelineState, String>(
  TimelineNotifier.new,
);
