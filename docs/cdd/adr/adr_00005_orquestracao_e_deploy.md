# ADR-0005 — Orquestração, Deploy e Escalabilidade

## Status
Aceito

## Data
2026-01-21

## Contexto

O sistema precisa escalar horizontalmente, suportar IA, processamento de vídeo e múltiplos clientes, mantendo alta disponibilidade.

---

## Decisão

Utilizar **Docker + Kubernetes** como padrão de orquestração.

---

## Arquitetura de Deploy

- Kubernetes
- Helm Charts
- Namespaces por ambiente

---

## Escalabilidade

- HPA baseado em CPU/GPU
- Workers de IA independentes
- Balanceamento via Ingress

---

## Observabilidade

- Prometheus
- Grafana
- Alertmanager

---

## Consequências

### Positivas
- Alta resiliência
- Fácil expansão

### Trade-offs
- Curva de aprendizado

---

## Alternativas Rejeitadas

- Deploy manual
- Docker Compose em produção

---

## Requisitos Não Funcionais

- Alta disponibilidade
- Recuperação automática

---

## Referências

- Kubernetes Docs
- CNCF

