# Arquitetura 5 Camadas — ZyraVision Mobile

## Diagrama de Dependencias

```
┌─────────────────────────────────────────────────────────────┐
│                         UI LAYER                             │
│  lib/ui/screens/    lib/ui/widgets/    lib/ui/router/        │
│  Flutter Widgets    Zy* Design System  GoRouter              │
│                                                              │
│  Depende de: Presentation, State (via Riverpod ref)          │
├─────────────────────────────────────────────────────────────┤
│                    PRESENTATION LAYER                        │
│  lib/presentation/viewmodels/    lib/presentation/formatters/│
│  Mapeamento estado→UI            Formatacao de dados         │
│                                                              │
│  Depende de: State, Domain (entities)                        │
├─────────────────────────────────────────────────────────────┤
│                     STATE / STORE LAYER                      │
│  lib/state/{feature}/                                        │
│  Riverpod Providers    AsyncNotifiers    Global State        │
│                                                              │
│  Depende de: Domain (use cases, entities, repositories)      │
├─────────────────────────────────────────────────────────────┤
│                    DOMAIN / ENGINE LAYER                     │
│  lib/domain/entities/     lib/domain/usecases/               │
│  lib/domain/repositories/ lib/domain/enums/                  │
│  Entidades (freezed)      Use Cases      Repository Interfaces│
│                                                              │
│  NAO DEPENDE DE NENHUMA OUTRA CAMADA                         │
│  (Inversao de Dependencia)                                   │
├─────────────────────────────────────────────────────────────┤
│                  DATA / INTEGRATION LAYER                    │
│  lib/data/repositories/   lib/data/datasources/              │
│  lib/data/models/         lib/data/mappers/                  │
│  Implementa interfaces do Domain                             │
│  API Client (Dio)  Local Storage (Hive)  WebSocket           │
│                                                              │
│  Depende de: Domain (implementa interfaces)                  │
│  Depende de: Core (api client, storage)                      │
└─────────────────────────────────────────────────────────────┘
```

## Regra de Ouro

```
UI → Presentation → State → Domain ← Data
                                ↑
                              Core
```

- Setas indicam direcao de dependencia
- Domain e o CENTRO — nao depende de ninguem
- Data IMPLEMENTA interfaces definidas em Domain
- Core fornece infraestrutura para todas as camadas

## Core Layer (Infraestrutura)

```
lib/core/
├── config/          # Constantes, env, feature flags
├── di/              # Providers de infraestrutura
├── network/         # API Client, interceptors
├── storage/         # Secure storage, Hive, prefs
├── auth/            # Auth service, biometric
├── push/            # FCM, notificacoes
├── streaming/       # HLS, WebRTC, PiP
├── sync/            # Offline sync
├── theme/           # Design tokens (cores, fontes, spacing)
└── utils/           # Validadores, formatadores
```

Core NAO e uma camada — e infraestrutura compartilhada.
Qualquer camada pode importar de Core.

## Fluxo de Dados

### Request (usuario → API)
```
UI (toque)
  → ViewModel (valida, prepara)
    → Provider (chama use case)
      → UseCase (regra de negocio)
        → Repository Interface
          → Repository Impl (Data)
            → Remote DataSource (Dio API call)
```

### Response (API → tela)
```
Remote DataSource (JSON response)
  → Model (json_serializable)
    → Mapper (Model → Entity)
      → Repository Impl (retorna Entity)
        → UseCase (aplica regras)
          → AsyncNotifier (atualiza estado)
            → Provider (notifica)
              → UI (rebuild automatico via ref.watch)
```

### Offline (sem rede)
```
Repository Impl
  → try: Remote DataSource ❌ (NetworkException)
  → catch: Local DataSource (Hive cache) ✅
    → retorna dados cacheados
      → UI exibe + banner "Modo Offline"
```

## Exemplo Concreto: Lista de Cameras

### Domain
```dart
// lib/domain/entities/camera.dart
@freezed
class Camera with _$Camera {
  const factory Camera({
    required String id,
    required String name,
    required CameraStatus status,
    required bool isOnline,
    required bool isRecording,
    String? thumbnailUrl,
    String? resolution,
    int? fps,
  }) = _Camera;
}

// lib/domain/repositories/camera_repository.dart
abstract class CameraRepository {
  Future<PaginatedResult<Camera>> getCameras({int page, String? search});
  Future<Camera> getCameraById(String id);
}

// lib/domain/usecases/cameras/get_cameras_usecase.dart
class GetCamerasUseCase {
  final CameraRepository _repository;
  GetCamerasUseCase(this._repository);

  Future<PaginatedResult<Camera>> call({int page = 1, String? search}) {
    return _repository.getCameras(page: page, search: search);
  }
}
```

### Data
```dart
// lib/data/models/camera_model.dart
@JsonSerializable()
class CameraModel {
  final String id;
  final String name;
  final String status;
  @JsonKey(name: 'is_online') final bool isOnline;
  @JsonKey(name: 'is_recording') final bool isRecording;
  @JsonKey(name: 'snapshot_url') final String? thumbnailUrl;

  factory CameraModel.fromJson(Map<String, dynamic> json) =>
    _$CameraModelFromJson(json);
}

// lib/data/mappers/camera_mapper.dart
class CameraMapper {
  static Camera toEntity(CameraModel model) => Camera(
    id: model.id,
    name: model.name,
    status: CameraStatus.fromString(model.status),
    isOnline: model.isOnline,
    isRecording: model.isRecording,
    thumbnailUrl: model.thumbnailUrl,
  );
}

// lib/data/repositories/camera_repository_impl.dart
class CameraRepositoryImpl implements CameraRepository {
  final CameraRemoteDataSource _remote;
  final CameraLocalDataSource _local;

  @override
  Future<PaginatedResult<Camera>> getCameras({int page = 1, String? search}) async {
    try {
      final response = await _remote.getCameras(page: page, search: search);
      final cameras = response.data.map(CameraMapper.toEntity).toList();
      await _local.cacheCameras(cameras);
      return PaginatedResult(data: cameras, meta: response.meta);
    } catch (_) {
      final cached = await _local.getCachedCameras();
      return PaginatedResult(data: cached, meta: PaginationMeta.empty());
    }
  }
}
```

### State
```dart
// lib/state/cameras/camera_providers.dart
final cameraListProvider = AsyncNotifierProvider.autoDispose<
  CameraListNotifier, PaginatedResult<Camera>
>(CameraListNotifier.new);

// lib/state/cameras/camera_list_notifier.dart
class CameraListNotifier extends AutoDisposeAsyncNotifier<PaginatedResult<Camera>> {
  @override
  Future<PaginatedResult<Camera>> build() async {
    final useCase = ref.read(getCamerasUseCaseProvider);
    return useCase(page: 1);
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() => ref.read(getCamerasUseCaseProvider)(page: 1));
  }

  Future<void> search(String query) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() => ref.read(getCamerasUseCaseProvider)(search: query));
  }
}
```

### UI
```dart
// lib/ui/screens/cameras/camera_list_screen.dart
class CameraListScreen extends ConsumerWidget {
  const CameraListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final camerasAsync = ref.watch(cameraListProvider);

    return ZyScaffold(
      appBar: const ZyAppBar(title: 'Cameras'),
      body: camerasAsync.when(
        data: (result) => _buildGrid(result.data),
        loading: () => const ZySkeleton.cameraGrid(),
        error: (e, _) => ZyEmptyState(
          message: 'Erro ao carregar cameras',
          actionLabel: 'Tentar novamente',
          onAction: () => ref.read(cameraListProvider.notifier).refresh(),
        ),
      ),
    );
  }
}
```
