enum SegmentStatus {
  writing,
  ready,
  uploading,
  uploaded,
  failed,
  unknown;

  static SegmentStatus fromString(String value) {
    return SegmentStatus.values.firstWhere(
      (e) => e.name == value.toLowerCase(),
      orElse: () => SegmentStatus.unknown,
    );
  }

  bool get isAvailable =>
      this == SegmentStatus.ready ||
      this == SegmentStatus.uploaded ||
      this == SegmentStatus.uploading;
}
