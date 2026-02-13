# ADR-001: Flutter como Framework Mobile

## Status
Aceito

## Contexto
O ZyraVision precisa de um aplicativo mobile para monitoramento de cameras CFTV.
Requisitos criticos: streaming HLS/WebRTC, push notifications, offline-first, performance nativa.

Opcoes consideradas:
1. **Flutter** — Cross-platform com compilacao nativa
2. **React Native** — Cross-platform com bridge JavaScript
3. **Nativo (Swift + Kotlin)** — Apps separados por plataforma

## Decisao
Adotar **Flutter 3.x** com **Dart 3.x**.

## Justificativas
- **Performance nativa** em video rendering (Skia/Impeller engine)
- **Single codebase** para iOS e Android (economia de 40-60% em dev time)
- **Dart** forte tipagem e null safety
- **Ecossistema maduro** para video: better_player, flutter_webrtc, video_player
- **Material Design 3** alinhado com o design system existente
- **Hot reload** acelera desenvolvimento
- **PiP nativo** suportado em ambas plataformas
- Time tem experiencia com Flutter

## Consequencias
- Dependencia de plugins Flutter para APIs nativas (camera, biometria, PiP)
- Atualizacoes de Flutter podem exigir migracao
- Tamanho do APK/IPA maior que nativo (~15-20MB base)
