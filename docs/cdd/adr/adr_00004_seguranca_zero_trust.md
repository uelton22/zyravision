# ADR-0004 — Segurança e Modelo Zero Trust

## Status
Aceito

## Data
2026-01-21

## Contexto

O sistema CFTV lida com dados sensíveis e infraestrutura crítica. É necessário garantir segurança em profundidade, evitando acessos não autorizados e vazamentos.

---

## Decisão

Adotar um **modelo Zero Trust**, onde nenhum componente confia implicitamente em outro.

---

## Estratégia

- Autenticação forte
- Autorização por RBAC
- Comunicação segura entre serviços
- Auditoria completa

---

## Medidas

- TLS 1.3 obrigatório
- Criptografia AES-256 em repouso
- Segregação de rede (VLAN)
- Rate limiting
- Logs imutáveis

---

## Consequências

### Positivas
- Alta segurança
- Conformidade legal

### Trade-offs
- Overhead operacional

---

## Alternativas Rejeitadas

- Segurança perimetral simples

---

## Requisitos Não Funcionais

- Confidencialidade
- Integridade
- Disponibilidade

---

## Referências

- NIST Zero Trust
- OWASP
