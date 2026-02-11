# ZyraVision — Context-Driven Development (CDD)

## O que e CDD

Context-Driven Development (CDD) e uma metodologia onde toda a documentacao, decisoes e padroes do projeto sao mantidos como contexto para IA e desenvolvedores. Isso garante que a IA do Cursor (e qualquer desenvolvedor) entenda completamente o projeto e mantenha padroes consistentes.

## Estrutura do CDD

```
ZyraVision/
├── .cursor/
│   ├── rules/                          # Regras para o Cursor AI
│   │   ├── zyravision-project.mdc      # Contexto geral (always apply)
│   │   ├── laravel-backend.mdc         # Padroes PHP/Laravel
│   │   ├── vue-frontend.mdc            # Padroes Vue/TypeScript
│   │   ├── design-system.mdc           # Regras do Design System
│   │   ├── api-standards.mdc           # Padroes de API REST
│   │   ├── database-standards.mdc      # Padroes PostgreSQL/Migrations
│   │   ├── testing-standards.mdc       # Padroes de testes
│   │   ├── security-standards.mdc      # Seguranca Zero Trust
│   │   ├── mobile-api.mdc             # API mobile (Flutter)
│   │   ├── landing-page.mdc           # Landing page comercial
│   │   └── docker-infra.mdc           # Docker e infraestrutura
│   └── skills/                         # Skills especializadas
│       ├── create-crud-module/         # Criar modulo CRUD completo
│       ├── create-vue-component/       # Criar componente Vue DS
│       ├── create-api-endpoint/        # Criar endpoint API
│       ├── create-migration/           # Criar migration PostgreSQL
│       └── create-test/               # Criar testes (Pest/Vitest)
│
├── docs/cdd/
│   ├── README.md                       # Este arquivo
│   ├── architecture/
│   │   ├── system-overview.md          # Visao macro do sistema
│   │   └── tech-stack.md              # Stack tecnologica oficial
│   ├── adr/
│   │   ├── INDEX.md                    # Indice de todas as ADRs
│   │   ├── adr_00026_*.md            # Compatibilidade mobile
│   │   ├── adr_00027_*.md            # API data sharing
│   │   └── adr_00028_*.md            # Design System tokens
│   ├── design-system/
│   │   ├── README.md                   # Visao geral do DS
│   │   ├── foundations/
│   │   │   ├── colors.md              # Paleta completa
│   │   │   ├── typography.md          # Fontes e escala
│   │   │   ├── spacing.md            # Espacamentos
│   │   │   ├── shadows-borders.md    # Sombras, bordas, radius
│   │   │   └── icons.md              # Heroicons mapeamento
│   │   ├── themes/
│   │   │   ├── light-theme.md        # Tema claro
│   │   │   └── dark-theme.md         # Tema escuro
│   │   ├── components/
│   │   │   ├── buttons.md            # ZyButton
│   │   │   ├── inputs.md             # ZyInput, ZySelect, etc
│   │   │   ├── tables.md             # ZyTable
│   │   │   ├── cards.md              # ZyCard
│   │   │   ├── modals.md             # ZyModal, ZyConfirm
│   │   │   ├── alerts-toasts.md      # ZyAlert, ZyToast
│   │   │   ├── navigation.md         # ZySidebar, ZyHeader
│   │   │   ├── badges-status.md      # ZyBadge, Status
│   │   │   └── pagination.md         # ZyPagination
│   │   ├── patterns/
│   │   │   ├── landing-page.md       # Landing page comercial (13 secoes)
│   │   │   ├── layouts.md            # Auth, Dashboard, Fullscreen
│   │   │   ├── crud-pages.md         # Index, Create, Edit, Show
│   │   │   ├── forms.md              # Padrao de formularios
│   │   │   ├── dashboards.md         # Widgets de dashboard
│   │   │   └── responsive-mobile.md  # Padroes responsivos
│   │   └── tokens/
│   │       └── design-tokens.json    # Tokens exportados
│   ├── api/
│   │   ├── README.md                  # Visao geral da API
│   │   ├── authentication.md          # Fluxos de autenticacao
│   │   ├── endpoints.md              # Lista completa de endpoints
│   │   └── mobile-api-spec.md        # Especificacao mobile
│   ├── mobile/
│   │   └── README.md                  # Arquitetura mobile Flutter
│   └── guidelines/
│       ├── coding-standards.md        # Padroes de codigo
│       └── git-workflow.md           # Fluxo de Git
│
├── adr_0001_*.md ... adr_00025_*.md   # ADRs originais
└── ZyraVision_cftv.md                 # Documento base do projeto
```

## Como Usar

### Para a IA do Cursor

1. As **Rules** (`.cursor/rules/`) sao carregadas automaticamente pela IA baseado no contexto
2. As **Skills** (`.cursor/skills/`) sao ativadas quando a IA detecta tarefas compativeis
3. O **Design System** (`docs/cdd/design-system/`) deve ser consultado antes de criar componentes
4. As **ADRs** documentam decisoes que nao devem ser alteradas sem nova ADR

### Para Desenvolvedores

1. Leia o `ZyraVision_cftv.md` para entender o projeto completo
2. Consulte as ADRs para entender decisoes arquiteturais
3. Siga o Design System para toda implementacao visual
4. Use as Skills como checklists para tarefas comuns

## Regras Absolutas

1. **Stack fixa**: Laravel 11, Vue 3.4, Tailwind 3.4, TypeScript 5, Flutter 3
2. **Design System first**: Criar componentes ANTES de telas
3. **API unificada**: Mesma API para web e mobile
4. **Multi-tenant**: TUDO isolado por tenant_id
5. **Zero Trust**: Validar tudo server-side
6. **Testes**: Minimo 80% cobertura
7. **Documentar**: ADR para decisoes, DS para visual, API docs para endpoints
