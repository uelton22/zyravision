# ADR-00015 — Aplicação Mobile Oficial com Flutter

**Status:** Aprovado  
**Data:** 2026-01-21  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

O ZyraVision é uma plataforma de monitoramento CFTV que necessita de presença mobile para:
- Operadores em campo
- Supervisores remotos
- Resposta rápida a incidentes
- Monitoramento 24/7 fora do NOC

A escolha do framework de desenvolvimento mobile impacta diretamente:
- Tempo de desenvolvimento
- Custo de manutenção
- Performance do aplicativo
- Qualidade da experiência do usuário
- Capacidade de reprodução de vídeo

---

## Decisão

O aplicativo mobile oficial do ZyraVision será desenvolvido em **Flutter**.

---

## Justificativa Técnica

### 1. Código Único (iOS/Android)

- Redução de 40-60% no tempo de desenvolvimento
- Equipe única para ambas plataformas
- Paridade de features garantida
- Lançamentos simultâneos

### 2. Performance Nativa

- Compilação para código nativo (AOT)
- Framework gráfico próprio (Skia)
- 60/120 FPS consistente
- Sem bridge de JavaScript

### 3. Ecossistema Maduro

- Desenvolvido e mantido pelo Google
- Grande comunidade (>160k stars GitHub)
- Plugins de alta qualidade
- Documentação extensa

### 4. Suporte a Vídeo

| Recurso | Suporte Flutter |
|---------|-----------------|
| HLS Playback | ✅ video_player, better_player |
| WebRTC | ✅ flutter_webrtc |
| Streaming Adaptativo | ✅ Nativo |
| Picture-in-Picture | ✅ Suportado |
| Background Audio/Video | ✅ Configurável |

---

## Arquitetura do App Flutter

### Estrutura de Pastas

```
lib/
├── core/
│   ├── api/           # Cliente HTTP, interceptors
│   ├── auth/          # Autenticação, tokens
│   ├── config/        # Configurações do app
│   └── utils/         # Helpers, extensions
├── data/
│   ├── models/        # Data models (JSON serialization)
│   ├── repositories/  # Data access layer
│   └── providers/     # State management
├── features/
│   ├── cameras/       # Lista e detalhe de câmeras
│   ├── live/          # Visualização ao vivo
│   ├── playback/      # Reprodução de gravações
│   ├── alerts/        # Notificações e alertas
│   ├── settings/      # Configurações do usuário
│   └── auth/          # Login, logout, sessão
├── widgets/
│   ├── common/        # Widgets reutilizáveis
│   ├── video/         # Players customizados
│   └── layout/        # Layouts base
└── main.dart
```

### State Management

- **Riverpod** como solução principal
- Separação clara entre UI e lógica
- Injeção de dependências nativa
- Testabilidade garantida

### Networking

- **Dio** para requisições HTTP
- Interceptors para autenticação
- Retry automático
- Cache local

---

## Funcionalidades do MVP Mobile

### Fase 1 - Essencial
- [ ] Login/Logout
- [ ] Lista de câmeras
- [ ] Live view (HLS)
- [ ] Push notifications
- [ ] Perfil do usuário

### Fase 2 - Completo
- [ ] Playback de gravações
- [ ] Timeline interativa
- [ ] Mosaico de câmeras
- [ ] Controle PTZ
- [ ] Alertas de IA

### Fase 3 - Avançado
- [ ] Exportação de clipes
- [ ] Busca por eventos
- [ ] Mapas interativos
- [ ] Offline mode parcial
- [ ] WebRTC para baixa latência

---

## Integração com Backend

### REST APIs

```dart
// Exemplo de integração
class CameraRepository {
  final Dio _dio;
  
  Future<List<Camera>> getCameras() async {
    final response = await _dio.get('/api/v1/cameras');
    return (response.data as List)
        .map((json) => Camera.fromJson(json))
        .toList();
  }
  
  Future<StreamUrl> getStreamUrl(String cameraId) async {
    final response = await _dio.get('/api/v1/cameras/$cameraId/stream');
    return StreamUrl.fromJson(response.data);
  }
}
```

### WebSockets

- Status de câmeras em tempo real
- Alertas instantâneos
- Sincronização de eventos

### Push Notifications

- Firebase Cloud Messaging (Android/iOS)
- Notificações locais para foreground
- Deep linking para navegação

---

## Requisitos de Qualidade

### Performance

| Métrica | Target |
|---------|--------|
| App startup | < 2s |
| Lista de câmeras | < 1s |
| Início de stream | < 3s |
| Uso de memória | < 200MB |
| Tamanho do app | < 50MB |

### Compatibilidade

- **iOS:** 12.0+
- **Android:** API 23+ (Android 6.0)
- **Tablets:** Layout adaptativo

---

## Segurança

### Medidas Obrigatórias

- Token storage seguro (Keychain/Keystore)
- Certificate pinning
- Ofuscação de código
- Detecção de jailbreak/root
- Biometria para desbloqueio

### Comunicação

- TLS 1.3 obrigatório
- Tokens com expiração curta
- Refresh token rotativo

---

## Consequências

### Positivas
- Desenvolvimento mais rápido
- Manutenção simplificada
- Performance excelente para vídeo
- Grande pool de desenvolvedores disponíveis

### Negativas / Trade-offs
- Tamanho do app maior que nativo puro
- Algumas APIs nativas requerem plugins
- Curva de aprendizado do Dart

---

## Alternativas Consideradas

### React Native

| Aspecto | Flutter | React Native |
|---------|---------|--------------|
| Performance | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| Vídeo | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| UI Customização | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| Ecossistema | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Desenvolvedores | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |

**Decisão:** Flutter oferece melhor performance para streaming de vídeo.

### Nativo Puro (Swift + Kotlin)

- ✅ Melhor performance possível
- ❌ Dobro do custo de desenvolvimento
- ❌ Duas equipes necessárias
- ❌ Tempo de mercado maior

**Decisão:** Custo-benefício não justifica para MVP.

---

## Roadmap de Desenvolvimento

| Fase | Duração | Entregáveis |
|------|---------|-------------|
| Setup | 2 semanas | Projeto, CI/CD, arquitetura |
| Auth + API | 2 semanas | Login, integração backend |
| Live View | 3 semanas | Lista câmeras, streaming HLS |
| Notifications | 2 semanas | Push, alertas, preferências |
| Playback | 3 semanas | Timeline, reprodução |
| Polish | 2 semanas | Testes, otimizações, release |

**Total estimado:** 14 semanas (MVP)

---

## Referências

- Flutter Documentation
- Dart Language Tour
- Flutter Video Player Packages
- Firebase Flutter Integration
- Material Design 3 for Flutter
