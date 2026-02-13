# Design System — Icones

## Biblioteca de Icones
- **Principal:** Heroicons (via `heroicons_flutter` ou mapeamento manual com `Icons`)
- **Fallback:** Material Icons (Flutter built-in)
- **Tamanhos padrao:** 16px (sm), 20px (md), 24px (lg), 32px (xl)

## Icones por Funcionalidade

### Navegacao
| Icone | Nome | Uso |
|-------|------|-----|
| dashboard | `Icons.dashboard_outlined` | Menu Dashboard |
| camera | `Icons.videocam_outlined` | Menu Cameras |
| play_circle | `Icons.play_circle_outline` | Menu Gravacoes |
| notifications | `Icons.notifications_outlined` | Menu Alertas |
| settings | `Icons.settings_outlined` | Menu Config |

### Status de Camera
| Icone | Nome | Cor | Uso |
|-------|------|-----|-----|
| circle_filled | `Icons.circle` | success | Online |
| circle_outlined | `Icons.circle_outlined` | danger | Offline |
| fiber_manual_record | `Icons.fiber_manual_record` | danger (pulse) | Gravando |
| warning | `Icons.warning_amber` | warning | Degradado |

### Acoes
| Icone | Nome | Uso |
|-------|------|-----|
| play | `Icons.play_arrow` | Iniciar stream/playback |
| stop | `Icons.stop` | Parar |
| pause | `Icons.pause` | Pausar |
| fullscreen | `Icons.fullscreen` | Tela cheia |
| picture_in_picture | `Icons.picture_in_picture` | PiP |
| download | `Icons.download` | Download |
| share | `Icons.share` | Compartilhar |
| delete | `Icons.delete_outline` | Excluir |
| edit | `Icons.edit_outlined` | Editar |
| search | `Icons.search` | Buscar |
| filter | `Icons.filter_list` | Filtrar |
| refresh | `Icons.refresh` | Atualizar |
| more_vert | `Icons.more_vert` | Menu opcoes |

### Informacao
| Icone | Nome | Uso |
|-------|------|-----|
| storage | `Icons.storage` | Armazenamento |
| cloud | `Icons.cloud_outlined` | Cloud/upload |
| wifi | `Icons.wifi` | Conectividade |
| wifi_off | `Icons.wifi_off` | Sem conexao |
| signal | `Icons.signal_cellular_alt` | Qualidade rede |
| lock | `Icons.lock_outline` | Seguranca |
| person | `Icons.person_outline` | Perfil |
| logout | `Icons.logout` | Sair |

### PTZ Controls
| Icone | Nome | Uso |
|-------|------|-----|
| arrow_up | `Icons.keyboard_arrow_up` | Pan up |
| arrow_down | `Icons.keyboard_arrow_down` | Pan down |
| arrow_left | `Icons.keyboard_arrow_left` | Pan left |
| arrow_right | `Icons.keyboard_arrow_right` | Pan right |
| zoom_in | `Icons.zoom_in` | Zoom in |
| zoom_out | `Icons.zoom_out` | Zoom out |
| home | `Icons.home` | PTZ home position |

## Regras
- Usar variante `outlined` por padrao (nao filled)
- Usar filled apenas para estado selecionado/ativo
- Tamanho padrao: 24px
- Cor padrao: textSecondary
- Icone com acao: primary
- Icone de status: cor semantica correspondente
- Sempre incluir `semanticLabel` para acessibilidade
