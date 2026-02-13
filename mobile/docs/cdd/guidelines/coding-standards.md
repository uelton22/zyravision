# Guidelines — Padroes de Codigo Flutter

## Convencoes de Nomenclatura

### Arquivos
```
snake_case.dart            — Todos os arquivos Dart
camera_list_screen.dart    — Telas
camera_repository.dart     — Interfaces
camera_repository_impl.dart — Implementacoes
camera_model.dart          — Models API
camera_mapper.dart         — Mappers
get_cameras_usecase.dart   — Use Cases
camera_list_notifier.dart  — Notifiers
camera_providers.dart      — Providers
zy_button.dart             — Widgets Design System
```

### Classes e Tipos
```dart
PascalCase                 — Classes, Enums, Typedefs
CameraListScreen           — Telas (sufixo Screen)
CameraRepository           — Interfaces (sem prefixo I)
CameraRepositoryImpl       — Implementacoes (sufixo Impl)
CameraModel                — Models API (sufixo Model)
GetCamerasUseCase          — Use Cases (sufixo UseCase)
CameraListNotifier         — Notifiers (sufixo Notifier)
CameraStatus               — Enums
```

### Variaveis e Funcoes
```dart
camelCase                  — Variaveis, funcoes, parametros
final cameraList           — Variaveis locais
_privateMethod()           — Metodos privados
getCameras()               — Metodos publicos
onTap                      — Callbacks
isOnline                   — Booleans (prefixo is/has/can/should)
```

### Providers
```dart
// camelCase + sufixo Provider
final cameraListProvider = ...;
final getCamerasUseCaseProvider = ...;
final authServiceProvider = ...;
final cameraRepositoryProvider = ...;
```

### Constantes
```dart
// camelCase (Dart convention, NAO SCREAMING_CASE)
const defaultPageSize = 15;
const maxRetries = 3;
const animationDuration = Duration(milliseconds: 200);
```

---

## Estrutura de Arquivos por Feature

```
lib/
├── domain/
│   ├── entities/
│   │   └── camera.dart              ← @freezed entity
│   ├── enums/
│   │   └── camera_status.dart       ← Enum com extensions
│   ├── repositories/
│   │   └── camera_repository.dart   ← Abstract class (interface)
│   └── usecases/
│       └── cameras/
│           ├── get_cameras_usecase.dart
│           └── get_camera_by_id_usecase.dart
├── data/
│   ├── models/
│   │   └── camera_model.dart        ← @JsonSerializable
│   ├── mappers/
│   │   └── camera_mapper.dart       ← Model → Entity
│   ├── datasources/
│   │   ├── camera_remote_datasource.dart
│   │   └── camera_local_datasource.dart
│   └── repositories/
│       └── camera_repository_impl.dart
├── state/
│   └── cameras/
│       ├── camera_providers.dart     ← Todos os providers
│       └── camera_list_notifier.dart ← AsyncNotifier
├── presentation/
│   └── viewmodels/
│       └── camera_list_viewmodel.dart
└── ui/
    └── screens/
        └── cameras/
            ├── camera_list_screen.dart
            └── widgets/
                └── camera_grid_item.dart
```

---

## Padroes de Codigo

### Widgets

```dart
// CORRETO: Widget const quando possivel
class ZyCameraCard extends StatelessWidget {
  const ZyCameraCard({
    super.key,
    required this.camera,
    this.onTap,
  });

  final Camera camera;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    // ...
  }
}
```

```dart
// CORRETO: ConsumerWidget para Riverpod
class CameraListScreen extends ConsumerWidget {
  const CameraListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final camerasAsync = ref.watch(cameraListProvider);
    // ...
  }
}
```

### Entities (Domain)

```dart
// CORRETO: freezed entity imutavel
@freezed
class Camera with _$Camera {
  const factory Camera({
    required String id,
    required String name,
    required CameraStatus status,
    required bool isOnline,
    @Default(false) bool isRecording,
    String? thumbnailUrl,
  }) = _Camera;
}
```

### Models (Data)

```dart
// CORRETO: json_serializable para API
@JsonSerializable(fieldRename: FieldRename.snake)
class CameraModel {
  final String id;
  final String name;
  final String status;
  @JsonKey(name: 'is_online')
  final bool isOnline;

  const CameraModel({
    required this.id,
    required this.name,
    required this.status,
    required this.isOnline,
  });

  factory CameraModel.fromJson(Map<String, dynamic> json) =>
    _$CameraModelFromJson(json);

  Map<String, dynamic> toJson() => _$CameraModelToJson(this);
}
```

### Use Cases

```dart
// CORRETO: Use Case com call() method
class GetCamerasUseCase {
  final CameraRepository _repository;

  const GetCamerasUseCase(this._repository);

  Future<PaginatedResult<Camera>> call({
    int page = 1,
    String? search,
  }) {
    return _repository.getCameras(page: page, search: search);
  }
}
```

### Providers

```dart
// CORRETO: Providers organizados em arquivo unico por feature
// lib/state/cameras/camera_providers.dart

// Repository
final cameraRepositoryProvider = Provider<CameraRepository>((ref) {
  return CameraRepositoryImpl(
    ref.read(cameraRemoteDataSourceProvider),
    ref.read(cameraLocalDataSourceProvider),
  );
});

// Use Cases
final getCamerasUseCaseProvider = Provider<GetCamerasUseCase>((ref) {
  return GetCamerasUseCase(ref.read(cameraRepositoryProvider));
});

// State
final cameraListProvider = AsyncNotifierProvider.autoDispose<
  CameraListNotifier, PaginatedResult<Camera>
>(CameraListNotifier.new);

// Simple State
final cameraSearchQueryProvider = StateProvider<String>((ref) => '');
```

---

## Regras de Importacao

### Ordem de Imports
```dart
// 1. Dart SDK
import 'dart:async';
import 'dart:convert';

// 2. Flutter SDK
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

// 3. Packages externos
import 'package:riverpod/riverpod.dart';
import 'package:dio/dio.dart';
import 'package:freezed_annotation/freezed_annotation.dart';

// 4. Projeto (absoluto)
import 'package:zyravision/core/network/api_client.dart';
import 'package:zyravision/domain/entities/camera.dart';

// 5. Relatives (apenas dentro da mesma feature)
import 'widgets/camera_grid_item.dart';
```

### Regra de Barrel Files
```dart
// NAO usar barrel files (index.dart) — imports explicitos sempre
// ERRADO: import 'package:zyravision/domain/entities.dart';
// CORRETO: import 'package:zyravision/domain/entities/camera.dart';
```

---

## Tratamento de Null Safety

```dart
// CORRETO: Evitar ! (bang operator) — use ?? ou if-null
final name = camera.name ?? 'Sem nome';

// CORRETO: Pattern matching
if (camera case Camera(:final thumbnailUrl?) ) {
  // thumbnailUrl garantidamente nao-null aqui
}

// CORRETO: Guard clause
final user = ref.read(currentUserProvider);
if (user == null) return;

// ERRADO: Nunca usar !
final name = camera.name!; // PROIBIDO
```

---

## Async/Await

```dart
// CORRETO: Sempre usar try/catch com tipos especificos
try {
  final cameras = await getCameras();
} on NetworkException catch (e) {
  // Tratar erro de rede
} on ApiException catch (e) {
  // Tratar erro de API
}

// CORRETO: AsyncValue.guard para Riverpod
state = await AsyncValue.guard(() async {
  return await useCase.call(page: 1);
});

// ERRADO: Nunca ignorar erros
await getCameras(); // Sem try/catch — PROIBIDO em prod
```

---

## Performance

### Widgets
```dart
// CORRETO: const construtores
const ZyButton(label: 'Salvar');

// CORRETO: Extrair widgets em classes separadas (nao metodos)
// ERRADO:
Widget _buildCard() => Card(...); // Rebuild desnecessario

// CORRETO:
class CameraGridItem extends StatelessWidget { ... }
```

### Listas
```dart
// CORRETO: ListView.builder para listas longas
ListView.builder(
  itemCount: cameras.length,
  itemBuilder: (context, index) => CameraGridItem(camera: cameras[index]),
);

// ERRADO: Column com children mapeados
Column(
  children: cameras.map((c) => CameraGridItem(camera: c)).toList(),
);
```

### Imagens
```dart
// CORRETO: cached_network_image
CachedNetworkImage(
  imageUrl: camera.thumbnailUrl,
  placeholder: (_, __) => const ZySkeleton.rect(),
  errorWidget: (_, __, ___) => const Icon(Icons.videocam_off),
);
```

---

## Documentacao

### Quando comentar
```dart
// CORRETO: Comentar logica complexa de negocio
/// Seleciona protocolo de streaming baseado na rede e modo de uso.
/// WiFi + single camera = LL-HLS, Mobile data = HLS, PTZ = WebRTC.
StreamProtocol selectProtocol(...) { ... }

// ERRADO: Nao comentar o obvio
/// Retorna o nome da camera  ← DESNECESSARIO
String get name => _name;
```

### Documentacao de API publica
```dart
// Apenas para classes/metodos publicos de Core e Domain
/// Repositorio de cameras.
///
/// Abstrai acesso a dados remotos e locais.
abstract class CameraRepository {
  /// Busca cameras paginadas com filtro opcional.
  Future<PaginatedResult<Camera>> getCameras({int page, String? search});
}
```
