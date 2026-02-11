# Git Workflow — ZyraVision

## Modelo de Branches

```
main ────────────────────────────────── producao (protegida)
  │
  └── develop ─────────────────────── desenvolvimento
        │
        ├── feature/cameras ────────── features (a partir de develop)
        ├── feature/auth
        ├── fix/camera-offline-bug ── bugfixes
        └── release/1.0.0 ──────────── releases
```

## Regras

### Branch `main`
- Protegida: somente via Pull Request
- Sempre deployavel
- Tags de versao (v1.0.0, v1.1.0)

### Branch `develop`
- Branch de integracao
- Features merge aqui primeiro
- CI/CD para staging

### Feature Branches
- Criar a partir de `develop`
- Nome: `feature/{descricao-curta}`
- PR para `develop`
- Squash merge

### Fix Branches
- Para bugs: `fix/{descricao}`
- Para hotfix em producao: criar de `main`, merge em `main` E `develop`

### Release Branches
- Criar de `develop` quando pronto para release
- `release/1.0.0`
- Merge em `main` (com tag) e `develop`

## Commit Messages

### Formato

```
type(scope): descricao curta

Descricao detalhada opcional.

Refs: #123
```

### Types

| Type | Uso |
|------|-----|
| feat | Nova funcionalidade |
| fix | Correcao de bug |
| refactor | Refatoracao (sem mudanca de comportamento) |
| docs | Documentacao |
| test | Testes |
| chore | Manutencao, deps, config |
| style | Formatacao (sem mudanca de logica) |
| perf | Melhoria de performance |
| ci | CI/CD |

### Scopes

```
cameras, streams, recordings, auth, users, tenants,
storage, ai, alerts, notifications, dashboard,
design-system, api, mobile, infra, docker
```

### Exemplos

```
feat(cameras): adicionar descoberta ONVIF
fix(streams): resolver reconexao automatica RTSP
refactor(storage): migrar para Flysystem v3
docs(adr): criar ADR-0026 mobile compatibility
test(auth): cobrir fluxo de refresh token
chore(docker): atualizar imagem PHP para 8.2.15
perf(recordings): otimizar query de timeline
ci(github): adicionar job de lint no PR
```

## Pull Requests

### Template

```markdown
## Descricao
[O que foi feito e por que]

## Tipo de Mudanca
- [ ] Feature
- [ ] Bug fix
- [ ] Refactoring
- [ ] Documentation
- [ ] Tests

## Checklist
- [ ] Testes adicionados/atualizados
- [ ] Segue Design System
- [ ] Documentacao atualizada
- [ ] Sem N+1 queries
- [ ] Tenant isolation verificado
- [ ] Migrations reversiveis (down funcional)
- [ ] API backward compatible

## Screenshots (se visual)
[Antes/Depois]
```

### Regras de PR
- Minimo 1 aprovacao
- CI passando (lint, testes, build)
- Branch atualizada com develop
- Squash merge para historico limpo
- Deletar branch apos merge

## Tags e Releases

### Versionamento Semantico

```
v{MAJOR}.{MINOR}.{PATCH}

MAJOR: Mudancas incompativeis
MINOR: Novas features (backward compatible)
PATCH: Bug fixes
```

### Exemplos

```
v1.0.0  — MVP release
v1.1.0  — Adicionar mobile support
v1.1.1  — Fix de bug em streaming
v2.0.0  — Refatoracao de API (breaking)
```

## .gitignore Essenciais

```
vendor/
node_modules/
.env
storage/app/
public/build/
*.sqlite
.phpunit.result.cache
coverage/
```
