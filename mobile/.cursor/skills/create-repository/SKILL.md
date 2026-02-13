---
name: create-repository
description: Cria repository (interface + implementacao) seguindo Clean Architecture. Use quando o usuario pedir para criar acesso a dados, integracao API, repository.
---
# Criar Repository — ZyraVision Mobile

## Step 1: Interface (Domain Layer)
`lib/domain/repositories/{name}_repository.dart`

```dart
abstract class CameraRepository {
  Future<List<Camera>> getCameras({int page = 1, int perPage = 25, String? search});
  Future<Camera> getCameraById(String id);
  Future<StreamInfo> getStreamStatus(String cameraId);
  Future<void> startStream(String cameraId);
  Future<void> stopStream(String cameraId);
}
```

## Step 2: Implementation (Data Layer)
`lib/data/repositories/{name}_repository_impl.dart`

```dart
class CameraRepositoryImpl implements CameraRepository {
  final CameraRemoteDataSource _remote;
  final CameraLocalDataSource _local;
  final CameraMapper _mapper;

  CameraRepositoryImpl(this._remote, this._local, this._mapper);

  @override
  Future<List<Camera>> getCameras({...}) async {
    try {
      final response = await _remote.getCameras(page: page, perPage: perPage, search: search);
      final cameras = response.data.map(_mapper.toEntity).toList();
      await _local.cacheCameras(cameras);
      return cameras;
    } catch (e) {
      // Fallback to local cache
      return _local.getCachedCameras();
    }
  }
}
```

## Checklist
- [ ] Interface no Domain layer
- [ ] Implementacao no Data layer
- [ ] Mapper Model ↔ Entity
- [ ] Cache fallback (se aplicavel)
- [ ] Error handling
- [ ] Provider no State layer
