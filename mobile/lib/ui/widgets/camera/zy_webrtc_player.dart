import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_webrtc/flutter_webrtc.dart';

import '../../../core/config/app_config.dart';

/// Player WebRTC via WHEP (WebRTC-HTTP Egress Protocol).
/// Conecta no MediaMTX para latência <1s.
/// Fallback automático para HLS via [onFallbackToHls].
class ZyWebrtcPlayer extends StatefulWidget {
  final String mediamtxPath;
  final VoidCallback? onFallbackToHls;
  final VoidCallback? onConnected;
  final double? height;
  final BoxFit fit;

  const ZyWebrtcPlayer({
    super.key,
    required this.mediamtxPath,
    this.onFallbackToHls,
    this.onConnected,
    this.height,
    this.fit = BoxFit.cover,
  });

  @override
  State<ZyWebrtcPlayer> createState() => _ZyWebrtcPlayerState();
}

class _ZyWebrtcPlayerState extends State<ZyWebrtcPlayer> {
  RTCPeerConnection? _pc;
  final _renderer = RTCVideoRenderer();
  bool _connected = false;
  bool _hasError = false;
  bool _disposed = false;
  bool _hasVideoTrack = false;
  bool _iceConnected = false;
  String? _whepResourceUrl;

  @override
  void initState() {
    super.initState();
    _init();
  }

  @override
  void didUpdateWidget(ZyWebrtcPlayer oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.mediamtxPath != widget.mediamtxPath) {
      _cleanup();
      _init();
    }
  }

  Future<void> _init() async {
    _disposed = false;
    try {
      await _renderer.initialize();
      if (_disposed) return;
      await _connectWhep();
    } catch (e) {
      debugPrint('[WebRTC] Init error: $e');
      _triggerFallback('Init error: $e');
    }
  }

  void _checkReady() {
    if (_hasVideoTrack && _iceConnected && !_connected && mounted && !_disposed) {
      debugPrint('[WebRTC] Video track + ICE ready → connected');
      setState(() => _connected = true);
      widget.onConnected?.call();
    }
  }

  Future<void> _connectWhep() async {
    final whepUrl =
        '${AppConfig.streamingBaseUrl}/webrtc/${widget.mediamtxPath}/whep';
    debugPrint('[WebRTC] WHEP URL: $whepUrl');

    final config = <String, dynamic>{
      'iceServers': [
        {'urls': 'stun:stun.l.google.com:19302'},
      ],
      'sdpSemantics': 'unified-plan',
    };

    _pc = await createPeerConnection(config);
    if (_disposed) {
      _pc?.close();
      return;
    }

    _pc!.onTrack = (RTCTrackEvent event) {
      if (event.track.kind == 'video' && event.streams.isNotEmpty) {
        debugPrint('[WebRTC] Video track received');
        if (mounted && !_disposed) {
          _renderer.srcObject = event.streams[0];
          _hasVideoTrack = true;
          _checkReady();
        }
      }
    };

    _pc!.onIceConnectionState = (RTCIceConnectionState state) {
      debugPrint('[WebRTC] ICE: $state');
      if (_disposed || !mounted) return;

      switch (state) {
        case RTCIceConnectionState.RTCIceConnectionStateConnected:
        case RTCIceConnectionState.RTCIceConnectionStateCompleted:
          _iceConnected = true;
          _checkReady();
          break;
        case RTCIceConnectionState.RTCIceConnectionStateFailed:
          _triggerFallback('ICE failed');
          break;
        case RTCIceConnectionState.RTCIceConnectionStateDisconnected:
          debugPrint('[WebRTC] ICE disconnected, waiting for recovery...');
          Future.delayed(const Duration(seconds: 3), () {
            if (mounted && !_disposed && !_iceConnected) {
              _triggerFallback('ICE disconnected timeout');
            }
          });
          _iceConnected = false;
          break;
        case RTCIceConnectionState.RTCIceConnectionStateClosed:
          if (!_disposed) {
            _triggerFallback('ICE closed');
          }
          break;
        default:
          break;
      }
    };

    await _pc!.addTransceiver(
      kind: RTCRtpMediaType.RTCRtpMediaTypeVideo,
      init: RTCRtpTransceiverInit(direction: TransceiverDirection.RecvOnly),
    );
    await _pc!.addTransceiver(
      kind: RTCRtpMediaType.RTCRtpMediaTypeAudio,
      init: RTCRtpTransceiverInit(direction: TransceiverDirection.RecvOnly),
    );

    final offer = await _pc!.createOffer();
    if (_disposed) return;

    await _pc!.setLocalDescription(offer);

    try {
      final dio = Dio();
      final response = await dio.post<String>(
        whepUrl,
        data: offer.sdp,
        options: Options(
          headers: {'Content-Type': 'application/sdp'},
          responseType: ResponseType.plain,
          validateStatus: (s) => s != null && s < 500,
        ),
      );
      dio.close();

      if (_disposed) return;

      if (response.statusCode == 201 && response.data != null) {
        _whepResourceUrl = response.headers.value('location');
        final answer = RTCSessionDescription(response.data!, 'answer');
        await _pc!.setRemoteDescription(answer);
        debugPrint('[WebRTC] SDP answer set, waiting for ICE...');
      } else {
        debugPrint('[WebRTC] WHEP returned ${response.statusCode}');
        _triggerFallback('WHEP ${response.statusCode}');
      }
    } on DioException catch (e) {
      debugPrint('[WebRTC] WHEP error: ${e.message}');
      if (!_disposed) {
        _triggerFallback('Network: ${e.message}');
      }
    }
  }

  void _triggerFallback(String reason) {
    debugPrint('[WebRTC] → Fallback HLS: $reason');
    if (!mounted || _disposed) return;
    _disposed = true;
    setState(() => _hasError = true);
    widget.onFallbackToHls?.call();
  }

  Future<void> _cleanup() async {
    _disposed = true;
    _hasVideoTrack = false;
    _iceConnected = false;
    _connected = false;
    _renderer.srcObject = null;

    if (_whepResourceUrl != null) {
      try {
        final dio = Dio();
        await dio.delete(_whepResourceUrl!);
        dio.close();
      } catch (_) {}
      _whepResourceUrl = null;
    }

    await _pc?.close();
    _pc = null;
  }

  @override
  void dispose() {
    _disposed = true;
    _pc?.close();
    _renderer.srcObject = null;
    _renderer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_hasError) {
      return SizedBox(
        height: widget.height,
        width: double.infinity,
        child: Container(
          color: Colors.black,
          child: const Center(
            child: SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: Colors.white54,
              ),
            ),
          ),
        ),
      );
    }

    if (!_connected) {
      return SizedBox(
        height: widget.height,
        width: double.infinity,
        child: Container(
          color: Colors.black,
          child: const Center(
            child: SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: Colors.white54,
              ),
            ),
          ),
        ),
      );
    }

    return SizedBox(
      height: widget.height,
      width: double.infinity,
      child: RTCVideoView(
        _renderer,
        objectFit: widget.fit == BoxFit.cover
            ? RTCVideoViewObjectFit.RTCVideoViewObjectFitCover
            : RTCVideoViewObjectFit.RTCVideoViewObjectFitContain,
        mirror: false,
      ),
    );
  }
}
