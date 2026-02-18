import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/repositories/camera_repository_impl.dart';
import '../../domain/entities/camera.dart';
import '../../domain/usecases/camera/get_camera_detail_usecase.dart';
import '../../domain/usecases/camera/get_cameras_usecase.dart';
import '../../domain/usecases/camera/toggle_stream_usecase.dart';
import 'camera_detail_notifier.dart';
import 'camera_list_notifier.dart';
import 'camera_list_state.dart';

// Use cases
final getCamerasUseCaseProvider = Provider<GetCamerasUseCase>((ref) {
  return GetCamerasUseCase(ref.read(cameraRepositoryProvider));
});

final getCameraDetailUseCaseProvider = Provider<GetCameraDetailUseCase>((ref) {
  return GetCameraDetailUseCase(ref.read(cameraRepositoryProvider));
});

final toggleStreamUseCaseProvider = Provider<ToggleStreamUseCase>((ref) {
  return ToggleStreamUseCase(ref.read(cameraRepositoryProvider));
});

// State notifiers
final cameraListProvider =
    AutoDisposeNotifierProvider<CameraListNotifier, CameraListState>(
  CameraListNotifier.new,
);

final cameraDetailProvider =
    AsyncNotifierProvider.family<CameraDetailNotifier, Camera, String>(
  CameraDetailNotifier.new,
);
