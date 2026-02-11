# ADR-00011 — Design System, UX/UI e Acessibilidade do ZyraVision

**Status:** Aprovado  
**Data:** 2026-01-21  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

## Contexto
O ZyraVision é uma plataforma profissional, crítica e de uso contínuo (24x7), utilizada para monitoramento de CFTV, análise de eventos, investigação forense e operação em ambientes corporativos, industriais e governamentais.

O design da aplicação **não é apenas estético**, mas um **componente funcional crítico**, impactando diretamente:
- Velocidade de resposta do operador
- Redução de erros humanos
- Escalabilidade da interface
- Adoção do sistema por equipes técnicas e não técnicas
- Uso prolongado sem fadiga visual

Portanto, é obrigatório adotar um **Design System profissional**, consistente e alinhado com padrões usados por grandes plataformas modernas.

## Decisão
Será adotado um **Design System próprio do ZyraVision**, baseado em práticas modernas de mercado, com os seguintes princípios obrigatórios:

### Princípios Fundamentais
- **Moderno e profissional** (estilo enterprise/SaaS)
- **Intuitivo** (zero curva de aprendizado para operações básicas)
- **Mobile-first** (pensado primeiro para telas pequenas)
- **Totalmente responsivo** (mobile, tablet, desktop, ultrawide)
- **Acessível (WCAG 2.1 AA)**
- **Consistente** em toda a aplicação
- **Orientado a operação crítica** (CFTV)

A IA **NÃO** deve criar estilos isolados por tela ou componente. Todo o design deve seguir estritamente o Design System definido.

## Stack de UI/UX
- **Frontend:** Vue 3 + Inertia.js
- **Estilos:** Tailwind CSS (versão definida no documento principal)
- **Interatividade:** Alpine.js (pontual)
- **Ícones:** Lucide Icons ou Heroicons
- **Gráficos:** Chart.js ou ECharts

## Design System do ZyraVision

### 1. Layout Base
- Layout em **grid flexível**
- Sidebar colapsável
- Header fixo
- Área principal com scroll controlado
- Suporte a multi-monitor

### 2. Paleta de Cores
- **Primary:** Tons de azul/ciano (confiança, tecnologia)
- **Secondary:** Cinza neutro
- **Success:** Verde
- **Warning:** Amarelo
- **Danger:** Vermelho
- **Info:** Azul claro

Regras:
- Contraste mínimo AA
- Dark Mode obrigatório
- Evitar cores saturadas em excesso

### 3. Tipografia
- Fonte principal: **Inter** ou **Roboto**
- Hierarquia clara:
  - H1: dashboards
  - H2: seções
  - H3: cards
  - Body: leitura prolongada
- Espaçamento confortável (line-height >= 1.5)

### 4. Componentes Padronizados
A IA deve **reutilizar sempre** os componentes abaixo:

- Botões (Primary, Secondary, Danger, Ghost)
- Inputs (text, select, date, search)
- Tabelas (listagem, paginação, filtros)
- Cards (status, métricas)
- Modais (confirmação, edição)
- Badges (status de câmera, AI, storage)
- Alerts e Toasts
- Skeleton loaders

Nenhum componente deve ser criado fora desse padrão.

### 5. Padrões de Tabela (Critical para CFTV)
- Listagens densas e legíveis
- Cabeçalho fixo
- Ordenação
- Filtros rápidos
- Ações contextuais
- Paginação clara

### 6. Estados Visuais
Todos os componentes devem prever:
- Loading
- Success
- Error
- Empty state
- Disabled

### 7. Experiência Operacional
- Feedback visual imediato
- Evitar modais desnecessários
- Ações críticas com confirmação
- Indicadores claros de status (online/offline/erro)

### 8. Mobile-First
- Grids adaptativos
- Menus colapsáveis
- Botões touch-friendly
- Priorizar ações essenciais

### 9. Acessibilidade (Obrigatório)
- WCAG 2.1 AA
- Navegação por teclado
- Focus visível
- Labels e aria-labels
- Não depender apenas de cor para status
- Compatibilidade com leitores de tela

### 10. Dark Mode
- Dark mode nativo
- Alternância manual
- Persistência por usuário
- Paleta específica (não inversão simples)

## Anti-Padrões (Proibido)
- Estilos inline
- CSS custom fora do Design System
- Cores diferentes para o mesmo significado
- Componentes duplicados
- Layouts inconsistentes
- Animações excessivas

## Consequências

### Positivas
- Interface altamente profissional
- Consistência visual
- Facilidade de manutenção
- Experiência de nível enterprise
- IA consegue gerar telas coerentes

### Negativas
- Maior esforço inicial
- Menor liberdade criativa pontual

Essas consequências são **aceitáveis e desejadas** para um sistema crítico como o ZyraVision.

## Referências de Mercado
O Design System do ZyraVision se inspira em:
- AWS Console
- Google Cloud Console
- Azure Portal
- Datadog
- Grafana
- Vercel Dashboard

## Instrução Final para IA
> Toda interface do ZyraVision **DEVE** seguir rigorosamente este ADR.  
> Nenhuma tela, componente ou fluxo pode violar o Design System definido.

---

