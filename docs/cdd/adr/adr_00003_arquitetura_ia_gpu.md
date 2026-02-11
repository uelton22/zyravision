# ADR-0003 — Arquitetura de IA e Uso de GPU

## Status
Aceito

## Data
2026-01-21

## Contexto

O sistema deve oferecer recursos de Inteligência Artificial para análise de vídeo, como detecção de movimento, pessoas, faces e placas, com suporte a ambientes com ou sem GPU.

É necessário evitar impacto direto no core da aplicação e permitir escalabilidade independente.

---

## Decisão

Implementar a IA como **microserviço desacoplado**, com suporte a:
- CPU (fallback)
- GPU NVIDIA (CUDA)
- Processamento assíncrono

---

## Arquitetura

- Serviço dedicado (Python)
- Comunicação via filas (Redis / RabbitMQ)
- Inferência batch ou real-time

---

## Tecnologias

- OpenCV
- YOLO (v8+)
- TensorRT (quando GPU)
- FFmpeg para frames

---

## Gerenciamento de GPU

- Detecção automática de GPU
- Configuração via runtime Docker
- Limitação de uso por worker

---

## Consequências

### Positivas
- Alta performance
- Isolamento de falhas

### Trade-offs
- Complexidade operacional
- Necessidade de drivers

---

## Alternativas Rejeitadas

- IA dentro do Laravel
- Processamento síncrono

---

## Requisitos Não Funcionais

- Latência controlada
- Escala horizontal

---

## Referências

- NVIDIA CUDA Docs
- OpenCV
- YOLO
