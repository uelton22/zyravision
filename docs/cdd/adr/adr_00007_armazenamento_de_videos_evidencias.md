# ADR-0007 – Armazenamento de Vídeos e Evidências

## Status
Aceito

## Contexto
Vídeos de CFTV geram grande volume de dados, exigindo retenção configurável, segregação por cliente/usuário e compatibilidade com diferentes backends de storage.

## Decisão
Implementar camada de abstração de storage:
- **Local:** ext4/xfs em SSD/HDD
- **Rede:** NFS, CIFS/SMB
- **Objeto:** S3-compatible (AWS S3, MinIO, Azure Blob)

Com políticas de:
- Retenção por perfil
- Quotas por usuário/empresa
- Criptografia em repouso

## Consequências
- Escalabilidade horizontal
- Possibilidade de ambientes híbridos
- Overhead de gerenciamento de storage

---