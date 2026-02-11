# ADR-0001 — Arquitetura Geral do Sistema CFTV Enterprise

## Status
Aceito

## Data
2026-01-21

## Contexto

A empresa necessita desenvolver um **Sistema Profissional e Avançado de Monitoramento CFTV (VMS)** capaz de operar em múltiplos cenários de infraestrutura (on‑premises, VPS, cloud, edge), com alto desempenho, escalabilidade, suporte a múltiplos tipos de armazenamento e integração com Inteligência Artificial.

Os sistemas atualmente utilizados no mercado apresentam limitações relevantes:
- Forte dependência de Windows
- Incompatibilidade com storage em cluster (SSH, NFS, S3)
- Arquiteturas monolíticas pouco escaláveis
- Baixa flexibilidade para IA e GPU

O objetivo é criar uma plataforma **vendor‑agnostic**, **cloud‑ready**, **multi‑tenant** e **IA‑ready**, utilizando tecnologias amplamente conhecidas no mercado e com grande suporte por ferramentas de IA.

---

## Decisão

Adotar uma **arquitetura modular baseada em Laravel + Inertia + Vue + Tailwind**, com separação clara de responsabilidades entre:

- Core Application (Laravel)
- Media Processing (FFmpeg Workers)
- Streaming & Playback
- Storage Abstraction
- AI Processing (CPU/GPU)
- Observabilidade e Segurança

A arquitetura deve permitir crescimento horizontal, tolerância a falhas e adaptação a diferentes perfis de clientes (small → enterprise).

---

## Stack Tecnológica Oficial

### Backend
- **Laravel 11**
- **PHP 8.2+**
- Laravel Sanctum (auth)
- Spatie Permissions (RBAC)
- Queue: Redis

### Frontend
- **Inertia.js**
- **Vue 3.4**
- **TypeScript**
- **Tailwind CSS 3.4.x**
- Alpine.js (uso pontual)

### Media & Streaming
- **FFmpeg (LGPL build)**
- RTSP (ingestão)
- HLS / DASH (playback)
- SRT / RTMP (opcional)

### Storage
- Local SSD
- NAS (NFS / CIFS)
- Object Storage (S3 / MinIO / Azure Blob)

### Banco de Dados
- PostgreSQL 15+
- Redis (cache, filas)

### AI / Video Analytics
- Microserviço dedicado
- OpenCV
- YOLO / TensorRT
- CPU fallback
- GPU (NVIDIA CUDA)

### Infraestrutura
- Docker / Docker Compose
- Kubernetes (futuro)
- NGINX
- Prometheus + Grafana
- ELK / Wazuh

---

## Justificativas da Decisão

### Laravel como Core
- Amplo ecossistema
- Alta maturidade
- Excelente suporte por IA
- Facilidade de manutenção

### Inertia + Vue
- Experiência SPA sem complexidade excessiva
- Melhor produtividade
- Ideal para dashboards e painéis CFTV

### Tailwind 3.4 (não 4.x)
- Estabilidade
- Compatibilidade com plugins
- Evita breaking changes

### Arquitetura Modular
- Escalabilidade horizontal
- Isolamento de falhas
- Permite edge computing

### Storage Abstraction
- Evita lock‑in
- Suporte a múltiplos cenários
- Facilita migração futura

---

## Consequências

### Positivas
- Sistema altamente escalável
- Compatível com múltiplas infraestruturas
- Pronto para IA e GPU
- Fácil evolução para SaaS

### Negativas / Trade‑offs
- Maior complexidade inicial
- Necessidade de DevOps mais maduro
- Curva de aprendizado maior

---

## Alternativas Consideradas

### Sistemas Prontos (Shinobi, ZoneMinder)
- ❌ Limitados
- ❌ Arquitetura rígida
- ❌ Difícil customização

### Stack Node.js completa
- ❌ Menor maturidade em ambientes corporativos
- ❌ Menor previsibilidade operacional

### Sistemas Windows‑based
- ❌ Incompatíveis com a infraestrutura atual
- ❌ Alto custo operacional

---

## Requisitos Não Funcionais

- Disponibilidade mínima: 99.5%
- Latência de playback < 3s
- Escalabilidade horizontal
- Segurança Zero Trust
- Observabilidade completa

---

## Decisões Relacionadas (Futuros ADRs)

- ADR‑0002 — Estratégia de Storage e Retenção
- ADR‑0003 — Arquitetura de IA e GPU
- ADR‑0004 — Segurança e Zero Trust
- ADR‑0005 — Observabilidade e SRE

---

## Referências

- FFmpeg Documentation
- ONVIF Specifications
- Laravel Architecture Guidelines
- CNCF Cloud Native Patterns

