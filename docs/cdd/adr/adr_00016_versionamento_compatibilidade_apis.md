# ADR-00016 — Versionamento e Compatibilidade de APIs

**Status:** Aprovado  
**Data:** 2026-01-21  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

O ZyraVision possui múltiplos clientes consumindo suas APIs:
- Aplicação web (Vue.js + Inertia)
- Aplicativo mobile (Flutter)
- Integrações de terceiros
- Sistemas de automação

Mudanças não planejadas na API podem causar:
- Quebra de aplicativos mobile em produção
- Falhas em integrações críticas
- Experiência degradada do usuário
- Perda de confiança de parceiros

Em sistemas CFTV, onde operações são **críticas e 24/7**, a estabilidade da API é fundamental.

---

## Decisão

Adotar **versionamento explícito de APIs** com política de compatibilidade e depreciação controlada.

### Formato de Versionamento

```
/api/v{major}/resource
```

Exemplos:
- `/api/v1/cameras`
- `/api/v2/cameras`
- `/api/v1/streams/{id}/start`

---

## Estratégia de Versionamento

### Regras de Incremento

| Tipo de Mudança | Ação | Exemplo |
|-----------------|------|---------|
| Novo endpoint | Não requer nova versão | Adicionar `/api/v1/analytics` |
| Novo campo opcional | Não requer nova versão | Adicionar `thumbnail_url` |
| Mudança de comportamento | **Nova versão maior** | Alterar formato de resposta |
| Remoção de campo | **Nova versão maior** | Remover `legacy_status` |
| Breaking change | **Nova versão maior** | Alterar autenticação |

### Coexistência de Versões

```
/api/v1/*  ← Versão estável (produção)
/api/v2/*  ← Nova versão (migração gradual)
```

Múltiplas versões DEVEM coexistir em produção.

---

## Ciclo de Vida de Versão

### Estados

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Active    │───>│ Deprecated  │───>│   Sunset    │───>│   Retired   │
│  (Current)  │    │  (6 meses)  │    │  (3 meses)  │    │  (Removed)  │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
```

### Duração Mínima

| Estado | Duração | Descrição |
|--------|---------|-----------|
| Active | Indefinido | Em uso, recebe features |
| Deprecated | 6 meses | Funciona, mas não recebe features |
| Sunset | 3 meses | Apenas correções críticas |
| Retired | - | Removido, retorna 410 Gone |

**Total mínimo de suporte:** 9 meses após deprecação

---

## Comunicação de Mudanças

### Headers de Resposta

```http
X-API-Version: v1
X-API-Deprecated: true
X-API-Sunset-Date: 2026-12-01
X-API-Successor: /api/v2
```

### Changelog

Manter um changelog público em:
- `/api/changelog`
- Documentação do desenvolvedor
- Release notes

### Notificações

- Email para desenvolvedores registrados
- Banner no dashboard de desenvolvedores
- Alertas na documentação

---

## Implementação no Laravel

### Estrutura de Rotas

```php
// routes/api.php

Route::prefix('v1')->group(function () {
    Route::apiResource('cameras', CamerasControllerV1::class);
    Route::post('streams/{id}/start', [StreamsControllerV1::class, 'start']);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('cameras', CamerasControllerV2::class);
    Route::post('streams/{id}/start', [StreamsControllerV2::class, 'start']);
});
```

### Estrutura de Controllers

```
app/Http/Controllers/
├── Api/
│   ├── V1/
│   │   ├── CamerasController.php
│   │   ├── StreamsController.php
│   │   └── UsersController.php
│   └── V2/
│       ├── CamerasController.php
│       ├── StreamsController.php
│       └── UsersController.php
```

### Middleware de Deprecação

```php
class ApiDeprecationMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if ($this->isDeprecated($request)) {
            $response->headers->set('X-API-Deprecated', 'true');
            $response->headers->set('X-API-Sunset-Date', '2026-12-01');
        }
        
        return $response;
    }
}
```

---

## Backward Compatibility

### Garantias

1. **Campos existentes** nunca serão removidos sem nova versão
2. **Tipos de dados** nunca serão alterados
3. **Status codes** seguirão padrão HTTP
4. **Paginação** terá formato consistente

### Formato de Resposta Padrão

```json
{
  "data": { ... },
  "meta": {
    "api_version": "v1",
    "deprecated": false,
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 100
    }
  }
}
```

---

## Documentação

### OpenAPI/Swagger

Cada versão DEVE ter documentação OpenAPI completa:
- `/api/v1/docs`
- `/api/v2/docs`

### Changelog por Versão

```markdown
## v2.0.0 (2026-06-01)

### Breaking Changes
- Novo formato de resposta para cameras
- Autenticação via Bearer Token obrigatória

### Novas Features
- Endpoint de analytics
- Suporte a WebRTC

### Deprecações
- v1 deprecated, sunset em 2026-12-01
```

---

## Consequências

### Positivas
- Evolução segura da API
- Menos quebras em produção
- Confiança de desenvolvedores
- Migração gradual de clientes

### Negativas / Trade-offs
- Manutenção de múltiplas versões
- Código duplicado temporariamente
- Documentação adicional

---

## Alternativas Rejeitadas

### Query Parameter Version
```
/api/cameras?version=1
```
- ❌ Menos explícito
- ❌ Dificulta cache

### Header Version
```
Accept: application/vnd.zyravision.v1+json
```
- ❌ Mais complexo
- ❌ Menos visível em logs

### Sem Versionamento
- ❌ Breaking changes afetam todos
- ❌ Impossível migração gradual

---

## Requisitos Não Funcionais

- Tempo de resposta: < 200ms (p99)
- Disponibilidade: 99.9%
- Rate limiting: 1000 req/min por API key
- Documentação atualizada em < 24h após release

---

## Referências

- Stripe API Versioning Strategy
- GitHub API Versioning
- Microsoft REST API Guidelines
- Google Cloud API Design Guide
