# Guidelines — Git Workflow

## Branches

```
main                         — Producao (protegida)
├── develop                  — Integracao
│   ├── feature/M1-auth      — Feature por milestone
│   ├── feature/M2-cameras   — Feature por milestone
│   ├── fix/login-biometric  — Bug fix
│   └── chore/update-deps    — Manutencao
└── release/1.0.0            — Release candidate
```

## Prefixos de Branch

| Prefixo | Uso | Exemplo |
|---------|-----|---------|
| `feature/` | Nova funcionalidade | `feature/M3-live-view` |
| `fix/` | Correcao de bug | `fix/hls-buffer-crash` |
| `chore/` | Manutencao, deps | `chore/update-riverpod-3` |
| `refactor/` | Refatoracao | `refactor/camera-entity` |
| `docs/` | Documentacao | `docs/api-endpoints` |
| `test/` | Apenas testes | `test/camera-repository` |

## Commits

### Formato
```
<tipo>(<escopo>): <descricao>

[corpo opcional]
```

### Tipos
| Tipo | Uso |
|------|-----|
| `feat` | Nova funcionalidade |
| `fix` | Correcao de bug |
| `refactor` | Refatoracao sem mudar comportamento |
| `test` | Adicionar/corrigir testes |
| `style` | Formatacao, lint (sem logica) |
| `docs` | Documentacao |
| `chore` | Build, deps, CI |
| `perf` | Otimizacao de performance |

### Escopos
| Escopo | Area |
|--------|------|
| `auth` | Autenticacao |
| `camera` | Cameras |
| `stream` | Streaming |
| `recording` | Gravacoes |
| `alert` | Alertas |
| `ui` | Design System |
| `core` | Infraestrutura |
| `state` | State management |

### Exemplos
```
feat(camera): adiciona listagem de cameras com paginacao
fix(stream): corrige buffer overflow em LL-HLS
refactor(auth): extrai token refresh para interceptor
test(camera): adiciona testes do CameraRepositoryImpl
chore(deps): atualiza riverpod para 2.5.0
perf(ui): otimiza rebuild da camera grid com const
```

## Pull Request

### Template
```markdown
## Descricao
Breve descricao do que foi feito.

## Tipo de Mudanca
- [ ] Feature
- [ ] Bug fix
- [ ] Refactor
- [ ] Tests

## Milestone
M1 / M2 / M3 / ...

## Checklist
- [ ] Testes unitarios passando
- [ ] Testes de widget passando
- [ ] Sem warnings de lint
- [ ] Build iOS OK
- [ ] Build Android OK
- [ ] Documentacao atualizada (se aplicavel)
- [ ] Screenshots (se UI)
```

## Code Review

### Criterios de Aprovacao
1. Testes passando (CI green)
2. Sem warnings de lint
3. Segue padroes de arquitetura (5 camadas)
4. Nomenclatura consistente (Zy*, sufixos corretos)
5. Performance (const widgets, ListView.builder)
6. Seguranca (sem dados sensiveis em logs)

## Versionamento

### Semantic Versioning
```
MAJOR.MINOR.PATCH+BUILD
1.0.0+1
1.1.0+15
2.0.0+100
```

- **MAJOR**: Breaking changes (ex: migracao de API)
- **MINOR**: Novas features (ex: multi-view)
- **PATCH**: Bug fixes
- **BUILD**: Numero incremental (CI)

### Tags
```bash
git tag -a v1.0.0 -m "Release 1.0.0 - MVP"
git push origin v1.0.0
```
