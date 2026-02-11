# ADR-00013 — Estratégia de Streaming Mobile do ZyraVision

**Status:** Aprovado  
**Data:** 2026-01-21  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

Aplicações mobile operam em condições de rede extremamente variáveis, incluindo:
- Conexões 4G/5G com variação de latência e banda
- WiFi com possíveis interferências
- Transições entre redes (handover)
- Perda temporária de conectividade

Em sistemas CFTV, a visualização de vídeo em dispositivos móveis é **crítica para operações de campo**, segurança e resposta a incidentes. Uma estratégia de streaming inadequada pode resultar em:
- Consumo excessivo de dados
- Buffering constante e experiência degradada
- Falhas de reprodução em redes instáveis
- Dreno de bateria

---

## Decisão

Adotar uma **estratégia de streaming híbrida e adaptativa** com três protocolos principais:

| Protocolo | Uso Principal | Latência |
|-----------|---------------|----------|
| **HLS** | Playback padrão e gravações | 6-30s |
| **LL-HLS** | Operações ao vivo | 2-5s |
| **WebRTC** | Alertas críticos e resposta rápida | <1s |

---

## Estratégia Detalhada

### 1. HLS como Protocolo Padrão

**Quando usar:**
- Visualização de gravações
- Monitoramento passivo
- Redes estáveis

**Configurações:**
- Segmentos de 4-6 segundos
- Playlist com 5 segmentos
- Multi-bitrate obrigatório (3 perfis mínimo)

**Perfis de Qualidade:**
| Perfil | Resolução | Bitrate | Uso |
|--------|-----------|---------|-----|
| Low | 480p | 500 Kbps | 3G/4G fraco |
| Medium | 720p | 1.5 Mbps | 4G/WiFi |
| High | 1080p | 4 Mbps | 5G/WiFi forte |

### 2. LL-HLS para Baixa Latência

**Quando usar:**
- Monitoramento ativo ao vivo
- Verificação de eventos
- Operações de campo

**Configurações:**
- Partial segments (200-500ms)
- Preload hints habilitado
- Buffer mínimo no player

### 3. WebRTC para Ultra Baixa Latência

**Quando usar:**
- Alertas críticos de IA
- Resposta a incidentes
- Comunicação PTZ em tempo real

**Considerações:**
- Maior consumo de recursos
- Requer TURN/STUN servers
- Limite de conexões simultâneas

---

## Adaptive Bitrate (ABR)

### Regras Obrigatórias

1. **Detecção automática de banda**
   - Medição contínua de throughput
   - Switch automático entre qualidades

2. **Configuração por usuário**
   - Permitir limite de qualidade máxima
   - Opção de economia de dados

3. **Fallback gracioso**
   - Reduzir qualidade progressivamente
   - Nunca interromper stream abruptamente

---

## Economia de Dados

### Estratégias Implementadas

- **Thumbnail preview** antes de abrir stream
- **Streaming sob demanda** (não pré-carregar)
- **Pause automático** quando app em background
- **Limite de streams simultâneos** por dispositivo

### Limites Sugeridos

| Rede | Streams Simultâneos | Qualidade Máxima |
|------|---------------------|------------------|
| 3G | 1 | 480p |
| 4G | 2 | 720p |
| 5G | 4 | 1080p |
| WiFi | 4 | 1080p |

---

## Consequências

### Positivas
- Alta compatibilidade com dispositivos iOS e Android
- Escalabilidade através de CDN
- Consumo eficiente de dados
- Experiência consistente em diferentes condições de rede

### Negativas / Trade-offs
- Maior complexidade de infraestrutura (múltiplos protocolos)
- Necessidade de CDN para escala
- Custo adicional de servidores TURN/STUN para WebRTC

---

## Alternativas Rejeitadas

### RTSP Direto
- ❌ Não suportado nativamente em browsers/apps
- ❌ Difícil de escalar
- ❌ Problemas com NAT/firewall

### MPEG-DASH Exclusivo
- ❌ Menor suporte em dispositivos Apple
- ❌ Mais complexo que HLS

---

## Requisitos Não Funcionais

- Latência máxima HLS: 30s
- Latência máxima LL-HLS: 5s
- Latência máxima WebRTC: 1s
- Tempo de início de playback: < 3s
- Taxa de falha de conexão: < 0.1%

---

## Referências

- Apple HLS Specification
- LL-HLS RFC
- WebRTC Standards (W3C)
- Bitmovin Streaming Best Practices
- Mux Video Mobile Guidelines
