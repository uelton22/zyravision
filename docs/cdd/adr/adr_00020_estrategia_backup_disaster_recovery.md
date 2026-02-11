# ADR-00020 — Estratégia de Backup e Disaster Recovery

**Status:** Aprovado  
**Data:** 2026-01-22  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

O ZyraVision é uma plataforma de monitoramento CFTV de **missão crítica**, onde a perda de dados pode resultar em:
- Perda de evidências legais
- Falhas de segurança
- Violações de compliance (LGPD, regulatórios)
- Interrupção de operações 24/7
- Danos financeiros e reputacionais

É **imperativo** ter uma estratégia robusta de backup e disaster recovery que garanta:
- Proteção contra perda de dados
- Recuperação rápida de serviços
- Conformidade com requisitos legais
- Continuidade de negócios

---

## Decisão

Implementar uma **estratégia de backup em múltiplas camadas** com plano de disaster recovery documentado e testado regularmente.

---

## Objetivos de Recuperação

### RPO e RTO por Tipo de Dado

| Tipo de Dado | RPO | RTO | Justificativa |
|--------------|-----|-----|---------------|
| **Banco de Dados** | 1 hora | 1 hora | Dados críticos de configuração |
| **Metadados de Vídeo** | 1 hora | 2 horas | Índices e referências |
| **Gravações de Vídeo** | 24 horas | 4 horas | Volume alto, importância variável |
| **Configurações** | 15 minutos | 30 minutos | Essencial para operação |
| **Logs de Auditoria** | 1 hora | 24 horas | Compliance, não crítico para operação |

### Definições

- **RPO (Recovery Point Objective):** Quantidade máxima de dados que pode ser perdida
- **RTO (Recovery Time Objective):** Tempo máximo para restaurar o serviço

---

## Estratégia de Backup

### 1. Banco de Dados (PostgreSQL)

#### Tipos de Backup

| Tipo | Frequência | Retenção | Destino |
|------|------------|----------|---------|
| WAL Archiving | Contínuo | 7 dias | Storage secundário |
| Incremental (pg_basebackup) | A cada 1 hora | 24 horas | Object Storage |
| Full Backup | Diário (02:00) | 30 dias | Object Storage |
| Full Backup Offsite | Semanal | 90 dias | Cloud diferente |

#### Configuração PostgreSQL

```conf
# postgresql.conf
archive_mode = on
archive_command = 'aws s3 cp %p s3://zyra-backup/wal/%f'
wal_level = replica
max_wal_senders = 3
```

### 2. Armazenamento de Vídeo

#### Estratégia por Tier

| Tier | Dados | Backup | Retenção |
|------|-------|--------|----------|
| Hot | Últimas 24h | Replicação síncrona | 7 dias |
| Warm | 2-30 dias | Replicação assíncrona | 30 dias |
| Cold | >30 dias | Snapshot semanal | 90 dias |

#### Replicação de Storage

```yaml
# Configuração MinIO
minio:
  replication:
    enabled: true
    targets:
      - endpoint: backup-storage.zyravision.local
        access_key: ${BACKUP_ACCESS_KEY}
        secret_key: ${BACKUP_SECRET_KEY}
        bucket: zyra-videos-backup
        sync_mode: async
        bandwidth_limit: 100Mbps
```

### 3. Configurações e Secrets

| Item | Método | Frequência | Destino |
|------|--------|------------|---------|
| .env files | Encrypted backup | A cada mudança | Vault/S3 |
| Certificates | Encrypted backup | Semanal | Vault |
| API Keys | Encrypted backup | A cada mudança | Vault |
| Helm values | Git + Backup | A cada mudança | Git + S3 |

---

## Arquitetura de Backup

### Componentes

```
┌─────────────────────────────────────────────────────────────────┐
│                     PRODUCTION ENVIRONMENT                       │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │ PostgreSQL│  │  Redis   │  │  MinIO   │  │  Config  │        │
│  └─────┬────┘  └─────┬────┘  └─────┬────┘  └─────┬────┘        │
│        │             │             │             │              │
│        ▼             ▼             ▼             ▼              │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              BACKUP AGENT / VELERO                       │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                     BACKUP STORAGE (SECONDARY)                   │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                       │
│  │ DB Dumps │  │ WAL Logs │  │  Videos  │                       │
│  └──────────┘  └──────────┘  └──────────┘                       │
└────────────────────────────┬────────────────────────────────────┘
                             │ (Async Replication)
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    OFFSITE BACKUP (CLOUD DR)                     │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                       │
│  │  AWS S3  │  │Azure Blob│  │   GCS    │                       │
│  └──────────┘  └──────────┘  └──────────┘                       │
└─────────────────────────────────────────────────────────────────┘
```

---

## Disaster Recovery Plan

### Cenários de Desastre

| Cenário | Severidade | RPO | RTO | Procedimento |
|---------|------------|-----|-----|--------------|
| Falha de disco único | Baixa | 0 | 0 | RAID rebuild automático |
| Falha de storage node | Média | 0 | 15min | Failover automático |
| Falha de datacenter | Alta | 1h | 4h | DR site activation |
| Corrupção de dados | Alta | 1h | 2h | Restore from backup |
| Ransomware | Crítica | 24h | 8h | Restore offsite + forensics |

### Procedimento de DR

#### Fase 1: Detecção e Avaliação (0-15 min)

1. Alertas automáticos via Prometheus/Alertmanager
2. Avaliação de escopo do incidente
3. Ativação do time de resposta
4. Decisão: failover ou restore

#### Fase 2: Ativação do DR Site (15-60 min)

1. Atualizar DNS para DR site
2. Restaurar último backup do PostgreSQL
3. Sincronizar storage de vídeos
4. Validar integridade de dados

#### Fase 3: Operação em DR (Durante incidente)

1. Monitoramento contínuo
2. Comunicação com stakeholders
3. Documentação de ações

#### Fase 4: Retorno ao Ambiente Principal (Pós-incidente)

1. Resolver causa raiz
2. Sincronizar dados do DR para produção
3. Testar ambiente principal
4. Failback gradual

---

## Testes de Recovery

### Cronograma de Testes

| Tipo de Teste | Frequência | Duração | Responsável |
|---------------|------------|---------|-------------|
| Restore de tabela específica | Semanal | 1h | DBA |
| Restore full do banco | Mensal | 4h | DevOps |
| Failover de storage | Trimestral | 2h | SRE |
| DR completo (tabletop) | Semestral | 1 dia | Time completo |
| DR completo (real) | Anual | 1 dia | Time completo |

### Checklist de Teste

```markdown
## Teste de Restore - [DATA]

- [ ] Backup identificado e validado
- [ ] Hash de integridade verificado
- [ ] Restore executado em ambiente isolado
- [ ] Dados verificados (sampling)
- [ ] Tempo de restore documentado
- [ ] Resultado: SUCESSO / FALHA
- [ ] Ações corretivas (se necessário)
```

---

## Monitoramento de Backups

### Métricas

| Métrica | Threshold | Alerta |
|---------|-----------|--------|
| backup_last_success_time | > 2h | Warning |
| backup_last_success_time | > 4h | Critical |
| backup_size_bytes | ±20% da média | Warning |
| backup_duration_seconds | > 2x média | Warning |
| replication_lag_seconds | > 300s | Critical |

### Dashboard Grafana

```promql
# Idade do último backup
time() - backup_last_success_timestamp{job="postgres-backup"}

# Taxa de sucesso de backups
sum(backup_success_total) / sum(backup_attempts_total) * 100
```

---

## Criptografia de Backups

### Requisitos

- Criptografia em trânsito: TLS 1.3
- Criptografia em repouso: AES-256
- Gerenciamento de chaves: HashiCorp Vault / AWS KMS

### Implementação

```bash
# Backup criptografado
pg_dump zyravision | gpg --encrypt --recipient backup@zyravision.com | \
  aws s3 cp - s3://zyra-backup/db/$(date +%Y%m%d_%H%M%S).sql.gpg
```

---

## Consequências

### Positivas
- Proteção contra perda de dados
- Conformidade regulatória
- Continuidade de negócios garantida
- Confiança de clientes

### Negativas / Trade-offs
- Custo adicional de storage
- Complexidade operacional
- Overhead de rede para replicação

---

## Alternativas Rejeitadas

### Backup Manual
- ❌ Propenso a erros humanos
- ❌ Inconsistente

### Apenas RAID
- ❌ Não protege contra corrupção
- ❌ Não protege contra desastres

### Backup apenas local
- ❌ Não protege contra desastre de site

---

## Requisitos Não Funcionais

- Backup success rate: > 99.9%
- Restore test success: > 99%
- Replication lag máximo: 5 minutos
- Recovery time (banco): < 1 hora

---

## Referências

- PostgreSQL Backup Best Practices
- Velero Documentation
- AWS Disaster Recovery Whitepaper
- MinIO Replication Guide
- NIST Contingency Planning Guide
