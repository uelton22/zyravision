# Design System — Media (ZyVideoPlayer, ZyThumbnail, ZyTimeline, ZyPtzControls)

## ZyVideoPlayer

Player de video para HLS e WebRTC.

### Layout — Normal

```
┌──────────────────────────────────────┐
│                                      │
│                                      │
│           VIDEO FRAME                │
│                                      │
│                                      │
│  ● LIVE              HD  PiP  ⛶     │  ← Controles overlay
└──────────────────────────────────────┘
```

### Layout — Fullscreen

```
┌──────────────────────────────────────────────────┐
│  ← Camera Estacionamento           HD  PiP  ⛶  │
│                                                  │
│                                                  │
│                                                  │
│              VIDEO FRAME                         │
│            (landscape)                           │
│                                                  │
│                                                  │
│                                                  │
│  ● LIVE  14:32:15           🔊  📸  ⋮          │
└──────────────────────────────────────────────────┘
```

### Layout — Playback

```
┌──────────────────────────────────────┐
│                                      │
│           VIDEO FRAME                │
│                                      │
│  ◄◄   ▶/❚❚   ►►   1x              │  ← Playback controls
│  ─────●────────────────── 05:32/60:00│  ← Seek bar
└──────────────────────────────────────┘
```

### Props

```dart
class ZyVideoPlayer extends ConsumerWidget {
  final String url;                   // HLS URL ou WebRTC offer
  final ZyPlayerMode mode;           // live, playback
  final bool autoPlay;                // Default: true
  final bool showControls;            // Default: true (auto-hide 3s)
  final bool allowFullscreen;         // Default: true
  final bool allowPip;                // Default: true
  final bool showQualityBadge;        // Default: true (HD/SD)
  final VoidCallback? onSnapshot;     // Capturar frame
  final VoidCallback? onError;
  final Duration? startPosition;      // Para playback, posicao inicial
  final double? playbackSpeed;        // 0.5, 1.0, 1.5, 2.0
}
```

### Controles Live
- Badge LIVE (verde, pulsante)
- Qualidade (HD/SD/480p)
- PiP button
- Fullscreen button
- Mute/unmute
- Snapshot (capturar frame)
- Menu (compartilhar, stream info)

### Controles Playback
- Play/Pause
- Seek bar (arrasta para posicao)
- Forward/Rewind (10s)
- Velocidade (0.5x, 1x, 1.5x, 2x)
- Timestamp atual / duracao total
- Download clip
- Fullscreen

### Visual
- Background: preto
- Controles: overlay semi-transparente
- Auto-hide controles: 3s apos ultimo toque
- Tap para mostrar/esconder controles
- Double-tap lado esquerdo: rewind 10s
- Double-tap lado direito: forward 10s
- Pinch to zoom (live view)
- Loading: ZyLoadingIndicator.circular sobre fundo preto

---

## ZyThumbnail

Thumbnail de camera com status e badges.

### Layout

```
┌──────────────────────┐
│                      │
│     SNAPSHOT         │  ← cached_network_image
│     (16:9)           │
│                      │
│  ● LIVE        720p  │  ← Badges sobrepostos
└──────────────────────┘
```

### Props

```dart
class ZyThumbnail extends StatelessWidget {
  final String? imageUrl;            // Snapshot URL
  final double aspectRatio;          // Default: 16/9
  final bool isLive;                 // Mostra badge LIVE
  final bool isRecording;            // Mostra badge REC
  final bool isOffline;              // Overlay escuro + icone
  final String? qualityLabel;        // "1080p", "720p", "480p"
  final double? borderRadius;        // Default: radiusLg
  final VoidCallback? onTap;
}
```

### Visual
- Aspect ratio: 16:9
- Border radius: 12px (radiusLg)
- Placeholder: skeleton shimmer
- Error: icone de camera com fundo cinza
- Offline overlay: semi-transparente com icone wifi_off
- Badge LIVE: success background, white text, top-left, padding 4px 8px
- Badge REC: danger background, white text, pulse animation, top-right
- Quality badge: surfaceVariant 80% opacity, bottom-right

---

## ZyTimeline

Timeline interativa para navegacao de gravacoes.

### Layout

```
  10:00    11:00    12:00    13:00    14:00    15:00
  │▓▓▓▓▓▓▓▓│▓▓▓▓░░░│▓▓▓▓▓▓▓▓│░░░░░░░░│▓▓▓▓▓▓▓▓│
                     ▲
                   cursor
                  12:34:56
```

### Props

```dart
class ZyTimeline extends StatelessWidget {
  final DateTime date;                // Dia selecionado
  final List<TimelineSegment> segments; // Periodos com gravacao
  final DateTime? currentPosition;    // Posicao do cursor
  final ValueChanged<DateTime> onSeek;// Ao arrastar cursor
  final double height;                // Default: 48px
}

class TimelineSegment {
  final DateTime start;
  final DateTime end;
  final TimelineSegmentType type;     // recording, gap, motion
}
```

### Visual
- Fundo: surfaceVariant
- Segmentos com gravacao: primary (com transparencia)
- Gaps: transparente (fundo visivel)
- Eventos de motion: warning dots
- Cursor: linha vertical primary 2px + circulo 12px
- Hora atual: label abaixo do cursor
- Scroll horizontal: pan gesture
- Zoom: pinch gesture (1h → 24h range)
- Marcas de hora: labelSmall, textTertiary
- Border radius: radiusMd (8px)

---

## ZyPtzControls

Controles de PTZ (Pan-Tilt-Zoom).

### Layout

```
         [ ▲ ]
  [ ◄ ]  [ ⌂ ]  [ ► ]
         [ ▼ ]

  [ - ]  ZOOM  [ + ]
```

### Props

```dart
class ZyPtzControls extends StatelessWidget {
  final VoidCallback onUp;
  final VoidCallback onDown;
  final VoidCallback onLeft;
  final VoidCallback onRight;
  final VoidCallback onHome;         // Posicao home
  final VoidCallback onZoomIn;
  final VoidCallback onZoomOut;
  final bool enabled;
  final bool showZoom;                // Default: true
}
```

### Visual
- Overlay semi-transparente
- Posicao: canto inferior direito do video
- Botoes: circulares, 44px, translucido
- Feedback haptico ao tocar
- Long press para movimento continuo
- Home button: centro do D-pad
