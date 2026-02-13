# ADR-004: Estrategia de Streaming Mobile

## Status
Aceito

## Contexto
O ZyraVision usa MediaMTX como proxy RTSP → HLS/WebRTC. O mobile precisa reproduzir
streams de cameras em tempo real com baixa latencia, suporte a PiP, multi-view e playback.

Protocolos disponiveis do MediaMTX:
- HLS (HTTP Live Streaming) — compativel universal
- LL-HLS (Low-Latency HLS) — Apple spec, 2-5s latencia
- WebRTC (WHEP) — ultra-low latency, <1s

## Decisao
Adotar estrategia de **streaming adaptativo** com 3 niveis:

| Protocolo | Latencia | Uso | Player |
|-----------|----------|-----|--------|
| HLS | 6-30s | Multi-view, background, 3G | better_player |
| LL-HLS | 2-5s | Live view padrao, WiFi | better_player |
| WebRTC | <1s | PTZ, dialogo, ultra-low | flutter_webrtc |

## Justificativas

### HLS como padrao
- Funciona em qualquer rede (3G, 4G, WiFi)
- Buffer adaptativo (ABR) ajusta qualidade automaticamente
- Suporte nativo iOS (AVPlayer), Android (ExoPlayer via better_player)
- Compativel com PiP em ambas plataformas
- Menor consumo de bateria (HTTP polling vs WebSocket)

### LL-HLS para live view
- Latencia aceitavel (2-5s) para monitoramento
- Usa mesma infra do HLS (HTTP)
- Suportado pelo MediaMTX com `hlsPartDuration: 500ms`
- Fallback automatico para HLS regular se rede lenta

### WebRTC para PTZ
- Latencia <1s essencial para controle PTZ em tempo real
- Bidirecional (permite audio se necessario)
- Usa WHEP (WebRTC HTTP Egress Protocol) do MediaMTX
- Consumo de bateria maior — usar apenas quando necessario

## Selecao Automatica

```dart
StreamProtocol selectProtocol({
  required NetworkType network,
  required bool isPtzActive,
  required int concurrentStreams,
}) {
  // PTZ ativo → sempre WebRTC
  if (isPtzActive) return StreamProtocol.webrtc;

  // Multi-view (3+ cameras) → HLS (economia de recursos)
  if (concurrentStreams >= 3) return StreamProtocol.hls;

  // WiFi → LL-HLS
  if (network == NetworkType.wifi) return StreamProtocol.llHls;

  // Mobile data → HLS (economia de dados)
  return StreamProtocol.hls;
}
```

## Consequencias
- 2 players diferentes (better_player + flutter_webrtc)
- Logica de switching entre protocolos adiciona complexidade
- WebRTC pode consumir mais bateria se mal gerenciado
- Testes precisam cobrir os 3 protocolos
- PiP disponivel apenas com HLS/LL-HLS (limitacao flutter_webrtc)
