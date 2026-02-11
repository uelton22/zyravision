# ADR-00019 — Pipeline de IA com GPU para Análise de Vídeo

Status: Aprovado
Data: 2026-01-21

## Contexto
Análises de IA em vídeo (movimento, faces, placas) exigem alto poder computacional e baixa latência.

## Decisão
Criar um **AI Pipeline desacoplado**, acelerado por GPU, integrado ao ZyraVision.

## Arquitetura
- Captura de frames (FFmpeg)
- Inferência (YOLO / OpenCV / DeepStream)
- Persistência de eventos
- Notificações em tempo real

## Tecnologias
- PyTorch / TensorFlow
- OpenCV
- NVIDIA DeepStream (opcional)
- ONNX Runtime

## Tipos de Detecção
- Movimento
- Pessoas
- Faces
- Placas (LPR)

## Consequências
- Alta performance
- Escalabilidade
- Possibilidade de múltiplos modelos

## Instrução para IA
A IA deve usar GPU quando disponível e fallback automático para CPU.
