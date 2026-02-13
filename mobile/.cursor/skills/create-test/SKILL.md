---
name: create-test
description: Cria testes para o ZyraVision Mobile. Use quando o usuario pedir para criar teste, testar, validar funcionalidade.
---
# Criar Teste — ZyraVision Mobile

## Unit Test (Domain Layer)
`test/domain/usecases/{feature}/{usecase}_test.dart`

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockCameraRepository extends Mock implements CameraRepository {}

void main() {
  late GetCamerasUseCase useCase;
  late MockCameraRepository mockRepository;

  setUp(() {
    mockRepository = MockCameraRepository();
    useCase = GetCamerasUseCase(mockRepository);
  });

  group('GetCamerasUseCase', () {
    test('deve retornar lista de cameras do repositorio', () async {
      when(() => mockRepository.getCameras())
          .thenAnswer((_) async => [testCamera]);

      final result = await useCase.execute();

      expect(result, [testCamera]);
      verify(() => mockRepository.getCameras()).called(1);
    });

    test('deve propagar erro quando repositorio falha', () async {
      when(() => mockRepository.getCameras())
          .thenThrow(Exception('Erro de conexao'));

      expect(() => useCase.execute(), throwsException);
    });
  });
}
```

## Widget Test
`test/ui/widgets/{widget}_test.dart`

```dart
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ZyButton', () {
    testWidgets('deve renderizar label corretamente', (tester) async {
      await tester.pumpWidget(
        MaterialApp(home: ZyButton(label: 'Entrar', onPressed: () {})),
      );

      expect(find.text('Entrar'), findsOneWidget);
    });

    testWidgets('deve estar desabilitado quando disabled=true', (tester) async {
      await tester.pumpWidget(
        MaterialApp(home: ZyButton(label: 'Entrar', disabled: true)),
      );

      final button = tester.widget<ElevatedButton>(find.byType(ElevatedButton));
      expect(button.onPressed, isNull);
    });
  });
}
```

## Checklist
- [ ] Testes em portugues
- [ ] Um conceito por teste
- [ ] Mock com mocktail
- [ ] Cenarios de sucesso + erro
- [ ] Widget tests para componentes Zy*
- [ ] Coverage > 70%
