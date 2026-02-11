# ADR-0002 — Estratégia de Storage e Retenção de Vídeo

## Status
Aceito

## Data
2026-01-21

## Contexto

O sistema CFTV precisa armazenar grandes volumes de vídeo, com diferentes requisitos de retenção, performance e custo. O ambiente deve suportar desde pequenas instalações locais até ambientes enterprise e cloud híbrida.

Há necessidade de:
- Suporte a múltiplos tipos de storage
- Retenção por perfil/usuário
- Alta taxa de escrita
- Leitura eficiente para playback e exportação
- Migração futura sem lock-in

---

## Decisão

Adotar uma **camada de abstração de storage**, permitindo múltiplos backends simultâneos, com políticas de retenção e ciclo de vida configuráveis por tenant e perfil.

---

## Estratégia de Storage

### Tipos Suportados
- Local SSD/NVMe
- NAS (NFS / CIFS)
- Object Storage (S3, MinIO, Azure Blob)

### Organização de Dados

```
/storage
 └── tenant_{id}
     └── camera_{id}
         └── YYYY/MM/DD/HH
             ├── segment_001.ts
             ├── segment_002.ts
             └── metadata.json
```

### Segmentação
- Segmentos de 1–5 minutos
- Facilita busca, retenção e recuperação

---

## Retenção e Lifecycle

- Retenção por perfil de usuário
- Regras automáticas de expurgo
- Migração de cold storage
- Verificação de integridade periódica

---

## Consequências

### Positivas
- Flexibilidade total
- Escala horizontal
- Redução de custo

### Trade-offs
- Complexidade de gerenciamento
- Necessidade de monitoramento constante

---

## Alternativas Rejeitadas

- Storage único local
- Dependência exclusiva de cloud

---

## Requisitos Não Funcionais

- Escrita contínua sem perda
- Playback fluido
- Failover automático

---

## Referências

- AWS S3 Lifecycle
- NFS Best Practices
- Video Storage Architectures
