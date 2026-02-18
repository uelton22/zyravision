import 'package:freezed_annotation/freezed_annotation.dart';

part 'stream_info.freezed.dart';

@freezed
class StreamInfo with _$StreamInfo {
  const factory StreamInfo({
    required String id,
    String? cameraId,
    @Default('inactive') String status,
    String? mediamtxPath,
    String? startedAt,
    String? errorMessage,
    StreamUrls? urls,
  }) = _StreamInfo;
}

@freezed
class StreamUrls with _$StreamUrls {
  const factory StreamUrls({
    String? hls,
    String? webrtc,
    String? rtsp,
  }) = _StreamUrls;
}
