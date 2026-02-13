# ADR-003: Riverpod como State Management

## Status
Aceito

## Contexto
O app precisa gerenciar estado complexo: auth, lista de cameras, streaming status,
alertas em real-time, cache offline, configuracoes de usuario.

Opcoes consideradas:
1. **Riverpod 2.x** — Reativo, type-safe, testavel
2. **BLoC/Cubit** — Event-driven, popular
3. **GetX** — Simples mas menos estruturado
4. **Provider** — Original, limitacoes conhecidas

## Decisao
Adotar **Riverpod 2.x** exclusivamente.

## Justificativas
- **Compile-time safety**: Providers tipados, sem runtime errors de tipo
- **Independente de BuildContext**: Pode usar em qualquer camada
- **Auto-dispose**: Libera recursos automaticamente quando widget sai da arvore
- **Familia de providers**: FutureProvider, AsyncNotifier, StateProvider — cada um para um caso
- **Testabilidade**: ProviderContainer para override em testes
- **Performance**: Rebuild granular (ref.watch de providers especificos)
- **Nao depende de code generation** (riverpod_generator e opcional)
- **Suporte a DevTools**: Inspecao de estado em tempo real

## Padroes Adotados

```dart
// StateProvider — estado simples
final searchQueryProvider = StateProvider<String>((ref) => '');

// FutureProvider — dados async imutaveis
final cameraDetailProvider = FutureProvider.family<Camera, String>((ref, id) {
  return ref.read(getCameraUseCaseProvider)(id);
});

// AsyncNotifierProvider — estado async mutavel
final cameraListProvider = AsyncNotifierProvider<CameraListNotifier, PaginatedResult<Camera>>(
  CameraListNotifier.new,
);

// StreamProvider — dados real-time
final cameraStatusProvider = StreamProvider.family<CameraStatus, String>((ref, cameraId) {
  return ref.read(webSocketServiceProvider).watchCameraStatus(cameraId);
});
```

## Consequencias
- Time precisa aprender API do Riverpod (nao e trivial)
- Migrar de Riverpod 2.x para 3.x pode exigir refatoracao
- Verbose em comparacao com GetX, mas mais seguro
