import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/timeline_data.dart';
import '../../domain/usecases/recording/get_available_dates_usecase.dart';
import '../../domain/usecases/recording/get_timeline_usecase.dart';
import 'recording_providers.dart';
import 'timeline_state.dart';

class TimelineNotifier extends AutoDisposeFamilyNotifier<TimelineState, String> {
  late final GetTimelineUseCase _getTimeline;
  late final GetAvailableDatesUseCase _getAvailableDates;

  @override
  TimelineState build(String arg) {
    _getTimeline = ref.read(getTimelineUseCaseProvider);
    _getAvailableDates = ref.read(getAvailableDatesUseCaseProvider);

    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);

    Future.microtask(() {
      loadTimeline(today);
      loadAvailableDates();
    });

    return TimelineState(
      isLoading: true,
      selectedDate: today,
    );
  }

  Future<void> loadTimeline(DateTime date) async {
    try {
      state = state.copyWith(
        isLoading: true,
        errorMessage: null,
        selectedDate: date,
      );

      final start = DateTime(date.year, date.month, date.day);
      final end = start.add(const Duration(days: 1));

      final data = await _getTimeline(
        arg,
        start: start,
        end: end,
      );

      state = state.copyWith(
        isLoading: false,
        data: data,
      );

      debugPrint(
        '[TimelineNotifier] Loaded ${data.totalSegments} segments, '
        '${data.ranges.length} ranges for camera $arg',
      );
    } catch (e) {
      debugPrint('[TimelineNotifier] Error loading timeline: $e');
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString(),
      );
    }
  }

  Future<void> loadAvailableDates() async {
    try {
      final dates = await _getAvailableDates(arg);
      state = state.copyWith(availableDates: dates);
    } catch (e) {
      debugPrint('[TimelineNotifier] Error loading available dates: $e');
    }
  }

  void selectDate(DateTime date) {
    loadTimeline(date);
  }

  void setPlaybackTime(DateTime time) {
    state = state.copyWith(currentPlaybackTime: time);
  }

  void setActiveSegment(String? segmentId) {
    state = state.copyWith(activeSegmentId: segmentId);
  }

  void setPlaying(bool playing) {
    state = state.copyWith(isPlaying: playing);
  }

  void setPlaybackSpeed(double speed) {
    state = state.copyWith(playbackSpeed: speed);
  }

  /// Find the segment at a given time offset in the timeline.
  PlaybackSegmentInfo? findSegmentAtTime(DateTime time) {
    final data = state.data;
    if (data == null) return null;

    for (final segment in data.segments) {
      if (segment.startsAt == null) continue;
      final segEnd = segment.endsAt ??
          segment.startsAt!.add(Duration(seconds: segment.durationSeconds));

      if (!time.isBefore(segment.startsAt!) && time.isBefore(segEnd)) {
        final offset = time.difference(segment.startsAt!).inSeconds;
        return PlaybackSegmentInfo(
          segmentId: segment.id,
          startsAt: segment.startsAt!,
          endsAt: segment.endsAt,
          durationSeconds: segment.durationSeconds,
          streamUrl: '',
          offsetSeconds: offset,
        );
      }
    }
    return null;
  }
}
