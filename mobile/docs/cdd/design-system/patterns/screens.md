# Design System — Padroes de Telas

## Telas do App

### 1. Splash Screen
```
┌──────────────────────┐
│                      │
│                      │
│     [ZyraVision]     │  ← Logo centralizado
│      ◉ loading       │  ← Indicador circular
│                      │
│                      │
└──────────────────────┘
```
- Duration: max 2s
- Verifica token → dashboard ou login

### 2. Login Screen
```
┌──────────────────────┐
│                      │
│     [ZyraVision]     │
│      Monitoramento   │
│      Inteligente     │
│                      │
│  ┌────────────────┐  │
│  │ Email          │  │
│  └────────────────┘  │
│  ┌────────────────┐  │
│  │ Senha      👁  │  │
│  └────────────────┘  │
│                      │
│  [ Entrar         ]  │  ← ZyButton fullWidth lg
│                      │
│  Esqueceu a senha?   │  ← Ghost link
│                      │
│  ─── ou ───          │
│                      │
│  [ 🔑 Biometria   ]  │  ← Se disponivel
│                      │
│  v1.0.0              │
└──────────────────────┘
```

### 3. Dashboard Screen
```
┌──────────────────────┐
│ ZyraVision    [🔍][👤]│
├──────────────────────┤
│                      │
│  ┌──────┐ ┌──────┐  │  ← Stats grid 2x2
│  │📹 42 │ │🟢 38 │  │
│  │Cameras│ │Online │  │
│  └──────┘ └──────┘  │
│  ┌──────┐ ┌──────┐  │
│  │🔴  4 │ │⏺  35│  │
│  │Offline│ │Gravando│ │
│  └──────┘ └──────┘  │
│                      │
│  Storage ████████░ 84%│  ← Barra de progresso
│                      │
│  Cameras Recentes    │
│  ┌──────────────┐   │  ← Lista horizontal
│  │  [thumb]  │  [t] │     scroll de cameras
│  │  Entrada  │  Gar │
│  │  ● Online │  ● O │
│  └──────────────┘   │
│                      │
│  Alertas Recentes    │
│  ┌──────────────────┐│
│  │🔴 Camera Offline ││
│  │   Estacionamento  ││
│  │   ha 5 min        ││
│  └──────────────────┘│
│  ┌──────────────────┐│
│  │🟡 Storage 80%    ││
│  │   ha 1 hora       ││
│  └──────────────────┘│
│                      │
├──────────────────────┤
│ 🏠  📹  ▶️  🔔  ⚙️ │
└──────────────────────┘
```

### 4. Camera List Screen
```
┌──────────────────────┐
│ Cameras          [⊞] │  ← Toggle grid/list
├──────────────────────┤
│ ┌────────────────┐   │
│ │ 🔍 Buscar...   │   │
│ └────────────────┘   │
│ Online(38) Offline(4)│  ← Filtro chips
│                      │
│ Grid 2 colunas:      │
│ ┌──────┐ ┌──────┐   │
│ │[thumb]│ │[thumb]│  │
│ │●LIVE  │ │      │  │
│ │Entrada│ │Garagem│  │
│ │●Online│ │●Offln │  │
│ └──────┘ └──────┘   │
│ ┌──────┐ ┌──────┐   │
│ │[thumb]│ │[thumb]│  │
│ │⬤REC  │ │      │  │
│ │Sala   │ │Portao │  │
│ │●Online│ │●Online│  │
│ └──────┘ └──────┘   │
│                      │
├──────────────────────┤
│ 🏠  📹  ▶️  🔔  ⚙️ │
└──────────────────────┘
```

### 5. Live View Screen
```
┌──────────────────────┐
│ ← Estacionamento [⛶] │
├──────────────────────┤
│                      │
│                      │
│    VIDEO PLAYER      │
│    (16:9)            │
│                      │
│ ● LIVE         HD    │
│                      │
├──────────────────────┤
│                      │
│  Camera Info         │
│  ┌──────────────────┐│
│  │ Status: ● Online ││
│  │ Resolucao: 1080p ││
│  │ FPS: 30          ││
│  │ Codec: H.264     ││
│  │ IP: 192.168.1.10 ││
│  └──────────────────┘│
│                      │
│  Acoes Rapidas       │
│  [📸 Snapshot]       │
│  [⏺ Gravar Clip]    │
│  [📡 WebRTC]        │
│  [🔇 Mute/Unmute]   │
│                      │
├──────────────────────┤
│ 🏠  📹  ▶️  🔔  ⚙️ │
└──────────────────────┘
```

### 6. Multi-View Screen (2x2)
```
┌──────────────────────┐
│ ← Multi View    [⛶]  │
├──────────────────────┤
│ ┌──────┐ ┌──────┐   │
│ │VIDEO │ │VIDEO │   │
│ │Cam 1 │ │Cam 2 │   │
│ │● LIVE│ │● LIVE│   │
│ └──────┘ └──────┘   │
│ ┌──────┐ ┌──────┐   │
│ │VIDEO │ │VIDEO │   │
│ │Cam 3 │ │Cam 4 │   │
│ │● LIVE│ │● LIVE│   │
│ └──────┘ └──────┘   │
│                      │
│ Tap para fullscreen  │
├──────────────────────┤
│ 🏠  📹  ▶️  🔔  ⚙️ │
└──────────────────────┘
```

### 7. Playback Screen
```
┌──────────────────────┐
│ ← Playback           │
├──────────────────────┤
│                      │
│    VIDEO PLAYER      │
│    (16:9)            │
│                      │
│ ◄◄  ▶/❚❚  ►►  1x   │
│ ──●──────── 05:32/60│
│                      │
├──────────────────────┤
│                      │
│  📅 12 Fev 2026     │
│                      │
│  Timeline:           │
│  10  11  12  13  14  │
│  ▓▓▓▓░▓▓▓▓░░▓▓▓▓▓▓ │
│              ▲       │
│                      │
│  Segmentos (24)      │
│  ┌──────────────────┐│
│  │ 10:00-10:05 120MB││
│  │ 10:05-10:10  95MB││
│  │ 10:10-10:15 110MB││
│  └──────────────────┘│
│                      │
├──────────────────────┤
│ 🏠  📹  ▶️  🔔  ⚙️ │
└──────────────────────┘
```

### 8. Settings Screen
```
┌──────────────────────┐
│ Configuracoes        │
├──────────────────────┤
│                      │
│  ┌────┐              │
│  │ JD │ Joao Da Silva│
│  │    │ joao@email   │
│  └────┘ Admin        │
│                      │
│  ─────────────       │
│                      │
│  👤 Perfil       →   │
│  🔔 Notificacoes →  │
│  📱 Dispositivos →  │
│  🎨 Aparencia    →  │
│  📊 Armazenamento→  │
│                      │
│  ─────────────       │
│                      │
│  🌙 Tema Escuro  [=] │
│  🔐 Biometria   [=] │
│  📹 Qualidade   720p│
│                      │
│  ─────────────       │
│                      │
│  🚪 Sair             │
│                      │
│  ZyraVision v1.0.0   │
│  Tenant: Empresa X   │
│                      │
├──────────────────────┤
│ 🏠  📹  ▶️  🔔  ⚙️ │
└──────────────────────┘
```

## Principios de Layout
- Safe area respeitada em todas as telas
- Pull-to-refresh em listas
- Skeleton loading no carregamento inicial
- Empty state quando sem dados
- Error state com botao retry
- Bottom nav persistente nas 5 telas principais
- Scroll suave com physics naturais por plataforma
