# Guidelines — Performance Mobile

## Metas de Performance

| Metrica | Meta | Critico |
|---------|------|---------|
| Cold start (app) | < 2s | < 3s |
| Warm start (app) | < 500ms | < 1s |
| HLS start (live) | < 3s | < 5s |
| WebRTC start | < 1s | < 2s |
| Frame rate (UI) | 60fps | > 30fps |
| Frame rate (video) | 30fps | > 15fps |
| Memoria (idle) | < 80MB | < 120MB |
| Memoria (1 stream) | < 120MB | < 150MB |
| Memoria (4 streams) | < 200MB | < 300MB |
| API response (lista) | < 500ms | < 1s |
| Bateria (1h uso ativo) | < 15% | < 25% |

---

## Widgets

### const Constructors
```dart
// CORRETO: Usar const sempre que possivel
const ZyButton(label: 'Salvar');
const SizedBox(height: 16);
const EdgeInsets.all(16);

// O compilador pula rebuild de widgets const
```

### Extrair Widgets (nao metodos)
```dart
// ERRADO: Metodo retornando widget (rebuild toda vez)
Widget _buildCameraCard(Camera camera) {
  return Card(child: Text(camera.name));
}

// CORRETO: Widget separado (framework otimiza rebuild)
class CameraGridItem extends StatelessWidget {
  const CameraGridItem({super.key, required this.camera});
  final Camera camera;

  @override
  Widget build(BuildContext context) {
    return Card(child: Text(camera.name));
  }
}
```

### Listas Longas
```dart
// CORRETO: ListView.builder (lazy rendering)
ListView.builder(
  itemCount: cameras.length,
  itemBuilder: (context, index) {
    return CameraGridItem(camera: cameras[index]);
  },
);

// CORRETO: SliverList para CustomScrollView
SliverList.builder(
  itemCount: cameras.length,
  itemBuilder: (context, index) {
    return CameraGridItem(camera: cameras[index]);
  },
);

// ERRADO: Column/ListView com todos os filhos
ListView(
  children: cameras.map((c) => CameraGridItem(camera: c)).toList(),
);
```

### RepaintBoundary
```dart
// Para widgets que atualizam frequentemente (badges, status)
RepaintBoundary(
  child: ZyBadge(
    label: 'LIVE',
    variant: ZyBadgeVariant.success,
  ),
);
```

---

## Imagens

### Cache de Rede
```dart
// Usar cached_network_image SEMPRE
CachedNetworkImage(
  imageUrl: camera.thumbnailUrl,
  memCacheWidth: 320,    // Resize em memoria
  maxWidthDiskCache: 640, // Resize em disco
  placeholder: (_, __) => const ZySkeleton.rect(),
  errorWidget: (_, __, ___) => const Icon(Icons.videocam_off),
);
```

### Limites de Cache
```dart
// Configurar no app startup
PaintingBinding.instance.imageCache.maximumSize = 100;         // 100 imagens
PaintingBinding.instance.imageCache.maximumSizeBytes = 50 << 20; // 50MB
```

### Thumbnails
```dart
// Solicitar resolucao adequada do backend
// 1 camera: 640px (detail)
// Grid 2 cols: 320px
// Grid 4 cols: 160px
// Lista: 120px
```

---

## Streaming

### Buffer Management
```dart
// HLS: Buffer minimo para inicio rapido
BetterPlayerConfiguration(
  controlsConfiguration: BetterPlayerControlsConfiguration(
    // Buffer inicial pequeno para start rapido
  ),
);

// Limitar buffers em multi-view
// 1 camera: buffer normal (10s)
// 2-4 cameras: buffer reduzido (5s)
// 5+ cameras: buffer minimo (3s)
```

### Dispose Correto
```dart
// SEMPRE dispose players ao sair da tela
@override
void dispose() {
  _betterPlayerController?.dispose();
  _webrtcConnection?.close();
  super.dispose();
}

// Com Riverpod: autoDispose
final playerProvider = Provider.autoDispose<BetterPlayerController>((ref) {
  final controller = BetterPlayerController(...);
  ref.onDispose(() => controller.dispose());
  return controller;
});
```

### Multi-View
```dart
// Limitar streams simultaneos
const maxConcurrentStreams = 4; // Mobile
const maxConcurrentStreamsTablet = 9; // Tablet

// Pausar streams fora da viewport
// Usar VisibilityDetector para detectar visibilidade
```

---

## State Management

### Granularidade de Providers
```dart
// CORRETO: Providers granulares (rebuild minimo)
final cameraNameProvider = Provider.family<String, String>((ref, id) {
  return ref.watch(cameraDetailProvider(id)).value?.name ?? '';
});

// UI: rebuild apenas quando nome muda
Text(ref.watch(cameraNameProvider(cameraId)));

// ERRADO: Watch de provider inteiro
Text(ref.watch(cameraDetailProvider(cameraId)).value?.name ?? '');
// ↑ Rebuild quando QUALQUER campo da camera muda
```

### select() para Filtragem
```dart
// CORRETO: select para fields especificos
final isOnline = ref.watch(
  cameraDetailProvider(id).select((state) => state.value?.isOnline ?? false),
);

// Rebuild apenas quando isOnline muda, ignora outras mudancas
```

### autoDispose
```dart
// CORRETO: autoDispose para telas (libera quando sai)
final cameraListProvider = AsyncNotifierProvider.autoDispose<...>(...);

// SEM autoDispose para estado global (auth, settings)
final authProvider = NotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);
```

---

## Rede

### Paginacao
```dart
// Carregar apenas pagina visivel
// Pre-fetch proxima pagina quando 80% scroll
// Limitar per_page: 15 (mobile), 30 (tablet)
```

### Compressao
```dart
// Aceitar gzip
dio.options.headers['Accept-Encoding'] = 'gzip';
```

### Debounce
```dart
// Search: 300ms debounce
// PTZ: 50ms throttle
// Pull-to-refresh: 1s cooldown
```

---

## Memoria

### Monitoramento
```dart
// Em debug mode, logar uso de memoria
if (kDebugMode) {
  final info = ProcessInfo.currentRss;
  debugPrint('Memory: ${(info / 1024 / 1024).toStringAsFixed(1)}MB');
}
```

### Limites por Cenario
```
Idle (dashboard): < 80MB
1 stream HLS: < 120MB
1 stream WebRTC: < 130MB
4 streams HLS: < 200MB
Playback: < 150MB
Background PiP: < 100MB
```

### Estrategias de Economia
1. Dispose players ao navegar para outra tela
2. Limitar cache de imagens (50MB)
3. Limpar cache de thumbnails ao receber memory warning
4. Usar autoDispose em providers de tela
5. Evitar manter dados de todas paginas em memoria (paginacao)

---

## Bateria

### Otimizacoes
1. Usar HLS sobre WebRTC quando possivel (menos CPU)
2. Reduzir FPS em multi-view (15fps)
3. Pausar streams quando app em background (exceto PiP)
4. Usar push notifications ao inves de polling
5. Sync incremental ao inves de full refresh
6. Modo escuro AMOLED para economizar display

### Background
```dart
// Limitar atividade em background
// Apenas: push notifications + PiP ativo
// NAO: polling, sync, analytics
```

---

## Profiling

### Flutter DevTools
```bash
flutter run --profile
# Abrir DevTools: http://localhost:9100
```

### Checklist Pre-Release
- [ ] Performance overlay: nenhum frame > 16ms
- [ ] Memory: sem leak (baseline estavel apos 10min)
- [ ] Network: sem requests desnecessarias (verificar DevTools)
- [ ] Startup: < 2s no device fisico
- [ ] Battery: < 15%/hora em uso continuo
- [ ] APK size: < 30MB (release)
