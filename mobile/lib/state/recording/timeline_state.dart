import 'package:freezed_annotation/freezed_annotation.dart';
import '../../domain/entities/recording_segment.dart';
import '../../domain/entities/timeline_data.dart';

part 'timeline_state.freezed.dart';

@freezed
class TimelineState with _$TimelineState {
  const factory TimelineState({
    @Default(false) bool isLoading,
    TimelineData? data,
    @Default([]) List<AvailableDate> availableDates,
    DateTime? selectedDate,
    DateTime? currentPlaybackTime,
    String? activeSegmentId,
    @Default(1.0) double playbackSpeed,
    @Default(false) bool isPlaying,
    String? errorMessage,
  }) = _TimelineState;

  const TimelineState._();

  bool get hasData => data != null && data!.hasRecordings;
  bool get hasError => errorMessage != null;

  RecordingSegment? get activeSegment {
    if (activeSegmentId == null || data == null) return null;
    return data!.segments.cast<RecordingSegment?>().firstWhere(
          (s) => s?.id == activeSegmentId,
          orElse: () => null,
        );
  }

  List<TimelineRange> get ranges => data?.ranges ?? [];
}
