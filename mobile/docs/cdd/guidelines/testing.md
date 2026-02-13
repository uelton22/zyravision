# Guidelines — Testes Flutter

## Piramide de Testes

```
        ╱╲
       ╱  ╲       Integration (E2E)     — 10%
      ╱    ╲      Fluxos criticos
     ╱──────╲
    ╱        ╲    Widget Tests           — 30%
   ╱          ╲   Renderizacao + interacao
  ╱────────────╲
 ╱              ╲  Unit Tests            — 60%
╱________________╲ Domain, Data, State
```

## Estrutura de Testes

```
test/
├── unit/
│   ├── domain/
│   │   ├── entities/
│   │   │   └── camera_test.dart
│   │   └── usecases/
│   │       └── get_cameras_usecase_test.dart
│   ├── data/
│   │   ├── models/
│   │   │   └── camera_model_test.dart
│   │   ├── mappers/
│   │   │   └── camera_mapper_test.dart
│   │   └── repositories/
│   │       └── camera_repository_impl_test.dart
│   └── state/
│       └── cameras/
│           └── camera_list_notifier_test.dart
├── widget/
│   ├── design_system/
│   │   ├── zy_button_test.dart
│   │   └── zy_camera_card_test.dart
│   └── screens/
│       ├── login_screen_test.dart
│       └── camera_list_screen_test.dart
├── integration/
│   ├── auth_flow_test.dart
│   └── camera_flow_test.dart
├── fixtures/
│   ├── camera_fixture.json
│   ├── cameras_list_fixture.json
│   └── login_response_fixture.json
└── helpers/
    ├── test_helpers.dart
    ├── mock_providers.dart
    └── pump_app.dart
```

## Convencoes

### Nomenclatura (PT-BR)
```dart
// Descricoes de teste em portugues
group('CameraRepository', () {
  test('deve retornar lista de cameras quando API responde com sucesso', () {
    // ...
  });

  test('deve retornar cache quando sem conexao', () {
    // ...
  });

  test('deve propagar erro quando API retorna 403', () {
    // ...
  });
});
```

### Padrao AAA (Arrange, Act, Assert)
```dart
test('deve retornar cameras paginadas', () async {
  // Arrange
  final mockRemote = MockCameraRemoteDataSource();
  final mockLocal = MockCameraLocalDataSource();
  when(() => mockRemote.getCameras(page: 1))
    .thenAnswer((_) async => cameraListFixture);
  final repository = CameraRepositoryImpl(mockRemote, mockLocal);

  // Act
  final result = await repository.getCameras(page: 1);

  // Assert
  expect(result.data, hasLength(3));
  expect(result.data.first.name, equals('Estacionamento'));
  verify(() => mockLocal.cacheCameras(any())).called(1);
});
```

## Mock com Mocktail

```dart
// test/helpers/mock_providers.dart
import 'package:mocktail/mocktail.dart';

class MockCameraRepository extends Mock implements CameraRepository {}
class MockCameraRemoteDataSource extends Mock implements CameraRemoteDataSource {}
class MockCameraLocalDataSource extends Mock implements CameraLocalDataSource {}
class MockAuthService extends Mock implements AuthService {}
class MockDio extends Mock implements Dio {}
```

## Testes por Camada

### Domain — Use Case
```dart
group('GetCamerasUseCase', () {
  late MockCameraRepository mockRepository;
  late GetCamerasUseCase useCase;

  setUp(() {
    mockRepository = MockCameraRepository();
    useCase = GetCamerasUseCase(mockRepository);
  });

  test('deve delegar chamada ao repository', () async {
    // Arrange
    when(() => mockRepository.getCameras(page: 1, search: null))
      .thenAnswer((_) async => PaginatedResult.empty());

    // Act
    await useCase(page: 1);

    // Assert
    verify(() => mockRepository.getCameras(page: 1, search: null)).called(1);
  });
});
```

### Data — Repository
```dart
group('CameraRepositoryImpl', () {
  late MockCameraRemoteDataSource mockRemote;
  late MockCameraLocalDataSource mockLocal;
  late CameraRepositoryImpl repository;

  setUp(() {
    mockRemote = MockCameraRemoteDataSource();
    mockLocal = MockCameraLocalDataSource();
    repository = CameraRepositoryImpl(mockRemote, mockLocal);
  });

  test('deve retornar dados remotos e cachear quando online', () async {
    when(() => mockRemote.getCameras(page: 1))
      .thenAnswer((_) async => apiResponse);
    when(() => mockLocal.cacheCameras(any()))
      .thenAnswer((_) async {});

    final result = await repository.getCameras(page: 1);

    expect(result.data, hasLength(2));
    expect(result.isOffline, isFalse);
    verify(() => mockLocal.cacheCameras(any())).called(1);
  });

  test('deve retornar cache quando sem rede', () async {
    when(() => mockRemote.getCameras(page: 1))
      .thenThrow(const NetworkException('Sem conexao'));
    when(() => mockLocal.getCachedCameras())
      .thenAnswer((_) async => cachedCameras);

    final result = await repository.getCameras(page: 1);

    expect(result.data, hasLength(2));
    expect(result.isOffline, isTrue);
  });
});
```

### Data — Model
```dart
group('CameraModel', () {
  test('deve deserializar JSON corretamente', () {
    final json = {
      'id': 'uuid-123',
      'name': 'Estacionamento',
      'status': 'online',
      'is_online': true,
      'is_recording': false,
    };

    final model = CameraModel.fromJson(json);

    expect(model.id, equals('uuid-123'));
    expect(model.name, equals('Estacionamento'));
    expect(model.isOnline, isTrue);
  });

  test('deve serializar para JSON corretamente', () {
    final model = CameraModel(
      id: 'uuid-123',
      name: 'Estacionamento',
      status: 'online',
      isOnline: true,
      isRecording: false,
    );

    final json = model.toJson();

    expect(json['is_online'], isTrue);
    expect(json['name'], equals('Estacionamento'));
  });
});
```

### Data — Mapper
```dart
group('CameraMapper', () {
  test('deve converter Model para Entity', () {
    final model = CameraModel(
      id: 'uuid',
      name: 'Camera 1',
      status: 'online',
      isOnline: true,
      isRecording: true,
    );

    final entity = CameraMapper.toEntity(model);

    expect(entity, isA<Camera>());
    expect(entity.status, equals(CameraStatus.online));
    expect(entity.isRecording, isTrue);
  });
});
```

### State — Notifier
```dart
group('CameraListNotifier', () {
  late ProviderContainer container;
  late MockGetCamerasUseCase mockUseCase;

  setUp(() {
    mockUseCase = MockGetCamerasUseCase();
    container = ProviderContainer(
      overrides: [
        getCamerasUseCaseProvider.overrideWithValue(mockUseCase),
      ],
    );
  });

  tearDown(() => container.dispose());

  test('deve carregar cameras no build', () async {
    when(() => mockUseCase(page: 1))
      .thenAnswer((_) async => paginatedCameras);

    final notifier = container.read(cameraListProvider.notifier);

    // Aguarda build completar
    await container.read(cameraListProvider.future);

    final state = container.read(cameraListProvider);
    expect(state.value?.data, hasLength(3));
  });

  test('deve atualizar estado ao fazer refresh', () async {
    when(() => mockUseCase(page: 1))
      .thenAnswer((_) async => paginatedCameras);

    await container.read(cameraListProvider.future);
    await container.read(cameraListProvider.notifier).refresh();

    verify(() => mockUseCase(page: 1)).called(2);
  });
});
```

### Widget — Design System
```dart
group('ZyButton', () {
  testWidgets('deve renderizar com label', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: ZyButton(
            label: 'Salvar',
            onPressed: () {},
          ),
        ),
      ),
    );

    expect(find.text('Salvar'), findsOneWidget);
  });

  testWidgets('deve chamar onPressed ao tocar', (tester) async {
    var pressed = false;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: ZyButton(
            label: 'Salvar',
            onPressed: () => pressed = true,
          ),
        ),
      ),
    );

    await tester.tap(find.text('Salvar'));
    expect(pressed, isTrue);
  });

  testWidgets('deve mostrar loading quando isLoading=true', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: ZyButton(
            label: 'Salvar',
            onPressed: () {},
            isLoading: true,
          ),
        ),
      ),
    );

    expect(find.byType(CircularProgressIndicator), findsOneWidget);
    expect(find.text('Salvar'), findsNothing);
  });
});
```

### Widget — Screen
```dart
group('CameraListScreen', () {
  testWidgets('deve mostrar skeleton durante loading', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          cameraListProvider.overrideWith(
            () => FakeCameraListNotifier(const AsyncLoading()),
          ),
        ],
        child: const MaterialApp(home: CameraListScreen()),
      ),
    );

    expect(find.byType(ZySkeleton), findsWidgets);
  });

  testWidgets('deve mostrar grid de cameras quando carregado', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          cameraListProvider.overrideWith(
            () => FakeCameraListNotifier(AsyncData(fakeCameras)),
          ),
        ],
        child: const MaterialApp(home: CameraListScreen()),
      ),
    );

    expect(find.byType(ZyCameraCard), findsNWidgets(3));
  });

  testWidgets('deve mostrar empty state quando lista vazia', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          cameraListProvider.overrideWith(
            () => FakeCameraListNotifier(AsyncData(emptyResult)),
          ),
        ],
        child: const MaterialApp(home: CameraListScreen()),
      ),
    );

    expect(find.byType(ZyEmptyState), findsOneWidget);
  });
});
```

## Fixtures

```dart
// test/fixtures/
// Usar arquivos JSON para respostas de API
// Usar fabricas Dart para entities

// test/helpers/factories.dart
Camera fakeCamera({
  String? id,
  String? name,
  CameraStatus status = CameraStatus.online,
}) {
  return Camera(
    id: id ?? const Uuid().v4(),
    name: name ?? 'Camera Test',
    status: status,
    isOnline: status == CameraStatus.online,
  );
}

List<Camera> fakeCameraList(int count) {
  return List.generate(count, (i) => fakeCamera(name: 'Camera $i'));
}
```

## Cobertura

### Meta
- Domain: >= 90%
- Data: >= 80%
- State: >= 80%
- Widgets: >= 70%
- UI Screens: >= 60%

### Comando
```bash
flutter test --coverage
genhtml coverage/lcov.info -o coverage/html
open coverage/html/index.html
```

## CI/CD

```yaml
# .github/workflows/test.yml
test:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: subosito/flutter-action@v2
    - run: flutter pub get
    - run: dart run build_runner build --delete-conflicting-outputs
    - run: flutter analyze
    - run: flutter test --coverage
    - run: |
        COVERAGE=$(lcov --summary coverage/lcov.info 2>&1 | grep 'lines' | awk '{print $2}' | sed 's/%//')
        if (( $(echo "$COVERAGE < 70" | bc -l) )); then
          echo "Coverage ${COVERAGE}% is below 70%"
          exit 1
        fi
```
