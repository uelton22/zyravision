# ADR-0006 – Pipeline de Vídeo (RTSP, HLS, WebRTC)

## Status
Aceito

## Contexto
O sistema de CFTV precisa ingerir, processar, gravar e distribuir streams de vídeo com diferentes requisitos de latência, qualidade e compatibilidade. Câmeras IP utilizam majoritariamente RTSP, enquanto usuários finais acessam via navegador ou apps.

## Decisão
Adotar um pipeline híbrido:
- **Ingestão:** RTSP (TCP/UDP) via FFmpeg/GStreamer
- **Processamento:** FFmpeg (decode, transcode, keyframes, overlays)
- **Distribuição:**
  - HLS/DASH para visualização padrão
  - WebRTC para baixa latência (tempo real)
  - RTMP/SRT para ingestões externas ou links instáveis

## Consequências
- Alta compatibilidade com câmeras e browsers
- Maior complexidade operacional
- Flexibilidade para múltiplos cenários (LAN, WAN, Cloud)

---