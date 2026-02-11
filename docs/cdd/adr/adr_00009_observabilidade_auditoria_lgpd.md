# ADR-0009 – Observabilidade, Auditoria e LGPD

## Status
Aceito

## Contexto
Sistemas de CFTV lidam com dados sensíveis, exigindo rastreabilidade, auditoria e conformidade legal.

## Decisão
Implementar stack de observabilidade e auditoria:
- **Logs:** ELK / OpenSearch
- **Métricas:** Prometheus + Grafana
- **Tracing:** OpenTelemetry
- **Auditoria:**
  - Acesso a vídeos
  - Download/exportação
  - Alterações de configuração

LGPD:
- Controle de acesso
- Logs imutáveis
- Políticas de retenção

## Consequências
- Maior confiança e conformidade
- Consumo adicional de recursos

---