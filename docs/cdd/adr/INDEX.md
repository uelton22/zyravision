# ADR Index — ZyraVision

## O que sao ADRs

Architecture Decision Records (ADRs) documentam decisoes arquiteturais significativas do projeto, incluindo contexto, alternativas consideradas e consequencias.

## Status

| Status | Descricao |
|--------|-----------|
| Aprovado | Decisao aceita e vigente |
| Proposto | Em discussao, aguardando aprovacao |
| Deprecado | Substituido por outra ADR |
| Rejeitado | Considerado e descartado |

## Convencao de Nomenclatura

- Formato: `adr_NNNNN_titulo.md` (5 digitos, zero-padded)
- Status padronizado: **Aprovado** (nao "Aceito")
- Data no formato: YYYY-MM-DD
- Estrutura obrigatoria: Status, Data, Contexto, Decisao, Alternativas, Consequencias

---

## Lista Completa de ADRs

### Arquitetura Core (0001-0010)

| # | Arquivo | Titulo | Status |
|---|---------|--------|--------|
| 0001 | adr_00001_arquitetura_sistema_cftv.md | Arquitetura Geral do Sistema CFTV Enterprise | Aprovado |
| 0002 | adr_00002_estrategia_storage_retencao.md | Estrategia de Storage e Retencao de Video | Aprovado |
| 0003 | adr_00003_arquitetura_ia_gpu.md | Arquitetura de IA e Uso de GPU | Aprovado |
| 0004 | adr_00004_seguranca_zero_trust.md | Seguranca e Modelo Zero Trust | Aprovado |
| 0005 | adr_00005_orquestracao_e_deploy.md | Orquestracao, Deploy e Escalabilidade | Aprovado |
| 0006 | adr_00006_pipeline_de_video_rtsp_hls_webrtc.md | Pipeline de Video (RTSP, HLS, WebRTC) | Aprovado |
| 0007 | adr_00007_armazenamento_de_videos_evidencias.md | Armazenamento de Videos e Evidencias | Aprovado |
| 0008 | adr_00008_modelo_licenciamento_multi_tenant.md | Modelo de Licenciamento e Multi-Tenant | Aprovado |
| 0009 | adr_00009_observabilidade_auditoria_lgpd.md | Observabilidade, Auditoria e LGPD | Aprovado |
| 0010 | adr_00010_estrategia_mvp_vs_produto_comercial.md | Estrategia de MVP vs Produto Comercial | Aprovado |

### Design e UX (0011, 0028)

| # | Arquivo | Titulo | Status |
|---|---------|--------|--------|
| 0011 | adr_00011_design_system_ux_ui_acessibilidade.md | Design System, UX/UI e Acessibilidade | Aprovado |
| 0028 | adr_00028_implementacao_design_system_tokens.md | Implementacao do Design System e Tokens | Aprovado |

### Mobile e API (0012-0017, 0026-0027)

| # | Arquivo | Titulo | Status |
|---|---------|--------|--------|
| 0012 | adr_00012_suporte_aplicacao_mobile_sincronizacao_arquitetura_mobile_first.md | Suporte a Aplicacao Mobile e Arquitetura Mobile-First | Aprovado |
| 0013 | adr_00013_estrategia_streaming_mobile.md | Estrategia de Streaming Mobile | Aprovado |
| 0014 | adr_00014_push_notifications_alertas_criticos.md | Push Notifications e Alertas Criticos | Aprovado |
| 0015 | adr_00015_aplicacao_mobile_oficial_flutter.md | Aplicacao Mobile Oficial com Flutter | Aprovado |
| 0016 | adr_00016_versionamento_compatibilidade_apis.md | Versionamento e Compatibilidade de APIs | Aprovado |
| 0017 | adr_00017_offline_mode_sincronizacao_parcial.md | Offline Mode e Sincronizacao Parcial | Aprovado |
| 0026 | adr_00026_compatibilidade_implementacao_mobile_completa.md | Compatibilidade e Implementacao Mobile Completa | Aprovado |
| 0027 | adr_00027_arquitetura_compartilhamento_dados_api_mobile.md | Arquitetura de Compartilhamento de Dados e API Mobile | Aprovado |

### Infraestrutura e Operacoes (0018-0025)

| # | Arquivo | Titulo | Status |
|---|---------|--------|--------|
| 0018 | adr_00018_infraestrutura_docker_orquestracao_suporte_gpu.md | Infraestrutura Docker, Orquestracao e Suporte GPU | Aprovado |
| 0019 | adr_00019_pipeline_ia_com_gpu_para_analise_video.md | Pipeline de IA com GPU para Analise de Video | Aprovado |
| 0020 | adr_00020_estrategia_backup_disaster_recovery.md | Estrategia de Backup e Disaster Recovery | Aprovado |
| 0021 | adr_00021_estrategia_testes_qualidade.md | Estrategia de Testes e Qualidade | Aprovado |
| 0022 | adr_00022_slas_metricas_performance.md | SLAs e Metricas de Performance | Aprovado |
| 0023 | adr_00023_estrategia_migracao_dados.md | Estrategia de Migracao de Dados | Aprovado |
| 0024 | adr_00024_gestao_logs_retencao.md | Gestao de Logs e Retencao | Aprovado |
| 0025 | adr_00025_integracoes_sistemas_externos.md | Integracoes com Sistemas Externos | Aprovado |

### Cameras e Dispositivos (0029-0031)

| # | Arquivo | Titulo | Status |
|---|---------|--------|--------|
| 0029 | adr_00029_suporte_ipv6_ddns_cameras.md | Suporte a IPv6 e DDNS para Cameras | Aprovado |
| 0030 | adr_00030_suporte_dvrs_como_cameras.md | Suporte a DVRs/NVRs como Fontes de Camera | Aprovado |
| 0031 | adr_00031_compatibilidade_multi_fabricante.md | Compatibilidade Multi-Fabricante de Cameras e DVRs | Aprovado |

---

## Mapa de Dependencias

```
ADR-0001 (Arquitetura Geral)
  ├── ADR-0002 (Storage)
  ├── ADR-0003 (IA/GPU) ──── ADR-0019 (Pipeline IA)
  ├── ADR-0004 (Seguranca)
  ├── ADR-0005 (Deploy) ──── ADR-0018 (Docker/K8s)
  ├── ADR-0006 (Pipeline Video)
  ├── ADR-0007 (Evidencias)
  ├── ADR-0008 (Multi-Tenant)
  ├── ADR-0009 (Observabilidade) ── ADR-0024 (Logs)
  └── ADR-0010 (MVP Strategy)

ADR-0011 (Design System) ──── ADR-0028 (DS Tokens)

ADR-0012 (Mobile Architecture)
  ├── ADR-0013 (Streaming Mobile)
  ├── ADR-0014 (Push Notifications)
  ├── ADR-0015 (Flutter)
  ├── ADR-0016 (API Versioning)
  ├── ADR-0017 (Offline Mode)
  ├── ADR-0026 (Mobile Completo) ── consolida 0012-0017
  └── ADR-0027 (Data Sharing API)

ADR-0020 (Backup/DR)
ADR-0021 (Testes)
ADR-0022 (SLAs)
ADR-0023 (Migracao)
ADR-0025 (Integracoes)

ADR-0006 (Pipeline Video)
  ├── ADR-0029 (IPv6/DDNS)
  ├── ADR-0030 (DVRs/NVRs) ──── ADR-0031 (Multi-Fabricante)
  └── ADR-0031 (Compatibilidade Fabricantes)
```

## Como Criar Nova ADR

1. Usar proximo numero sequencial: `adr_00032_titulo.md`
2. Seguir template padrao (Status, Data, Contexto, Decisao, Alternativas, Consequencias)
3. Adicionar neste INDEX.md
4. Cross-referenciar ADRs relacionadas
