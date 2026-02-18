import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/camera.dart';
import 'camera_providers.dart';

class CameraDetailNotifier extends FamilyAsyncNotifier<Camera, String> {
  @override
  Future<Camera> build(String arg) async {
    return _fetchCamera(arg);
  }

  Future<Camera> _fetchCamera(String cameraId) async {
    final useCase = ref.read(getCameraDetailUseCaseProvider);
    return useCase(cameraId);
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() => _fetchCamera(arg));
  }

  Future<void> startStream() async {
    try {
      final toggleStream = ref.read(toggleStreamUseCaseProvider);
      await toggleStream.start(arg);
      await refresh();
    } catch (e) {
      state = AsyncError(e, StackTrace.current);
    }
  }

  Future<void> stopStream() async {
    try {
      final toggleStream = ref.read(toggleStreamUseCaseProvider);
      await toggleStream.stop(arg);
      await refresh();
    } catch (e) {
      state = AsyncError(e, StackTrace.current);
    }
  }
}
