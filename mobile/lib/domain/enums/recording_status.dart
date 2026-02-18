enum RecordingStatus {
  recording,
  completed,
  failed,
  deleted,
  unknown;

  static RecordingStatus fromString(String value) {
    return RecordingStatus.values.firstWhere(
      (e) => e.name == value.toLowerCase(),
      orElse: () => RecordingStatus.unknown,
    );
  }

  bool get isCompleted => this == RecordingStatus.completed;
  bool get isRecording => this == RecordingStatus.recording;
  bool get isFailed => this == RecordingStatus.failed;
}
