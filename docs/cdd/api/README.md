# API Documentation — ZyraVision

## Visao Geral

A API do ZyraVision e uma API REST versionada que serve tanto o frontend web (via Inertia) quanto o app mobile (via REST puro). Autenticacao via Laravel Sanctum.

## Base URL

```
Production:  https://api.zyravision.com/api/v1
Staging:     https://staging-api.zyravision.com/api/v1
Development: http://localhost:8000/api/v1
```

## Autenticacao

Todas as rotas (exceto login/registro) requerem autenticacao.

### Web (Cookie-based)
```
POST /login → Set-Cookie: laravel_session
Requests subsequentes: Cookie automatico
```

### Mobile/API (Token-based)
```
POST /api/v1/auth/login → { token, refreshToken, expiresAt }
Requests: Authorization: Bearer {token}
```

## Documentacao por Modulo

| Modulo | Arquivo | Descricao |
|--------|---------|-----------|
| Autenticacao | [authentication.md](authentication.md) | Login, registro, tokens |
| Endpoints | [endpoints.md](endpoints.md) | Lista completa de endpoints |
| Mobile API | [mobile-api-spec.md](mobile-api-spec.md) | Especificacoes mobile |

## Headers Obrigatorios

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}     # Rotas autenticadas
X-Tenant-Id: {uuid}              # Automatico via token
```

## Headers Mobile Adicionais

```
X-Device-Id: {uuid}
X-App-Version: 1.0.0
X-Platform: android|ios
Accept-Language: pt-BR
```

## Formato de Erro

```json
{
  "message": "Descricao amigavel do erro",
  "code": "CAMERA_NOT_FOUND",
  "errors": {
    "name": ["O campo nome e obrigatorio"]
  }
}
```

## Rate Limiting

Headers de response:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 55
X-RateLimit-Reset: 1706304000
```
