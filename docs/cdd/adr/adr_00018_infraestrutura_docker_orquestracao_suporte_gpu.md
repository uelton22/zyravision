# ADR-00018 — Infraestrutura Docker, Orquestração e Suporte a GPU

Status: Aprovado
Data: 2026-01-21

## Contexto
O ZyraVision deve ser executável em múltiplos cenários: ambiente local, servidores bare metal, VPS, cloud e clusters híbridos, com ou sem aceleração por GPU.

## Decisão
Adotar **Docker como padrão de empacotamento**, com suporte a:
- Docker Compose (MVP)
- Kubernetes (escala futura)
- Pass-through de GPU (NVIDIA / Intel / AMD)

## Componentes Containerizados
- zyra-api (Laravel)
- zyra-media-worker (FFmpeg)
- zyra-ai-worker (IA)
- PostgreSQL
- Redis
- MinIO / Storage
- Nginx / Traefik

## GPU Support
- NVIDIA CUDA (nvidia-container-toolkit)
- Intel VAAPI / OpenVINO
- AMD ROCm (futuro)

## Consequências
- Portabilidade total
- Escalabilidade horizontal
- Uso eficiente de hardware

## Instrução para IA
Todo serviço deve ser dockerizado e preparado para execução com ou sem GPU.
