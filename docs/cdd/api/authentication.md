# Autenticacao — ZyraVision API

## Fluxo de Login Web

```
1. POST /login { email, password }
2. Laravel Sanctum cria sessao
3. Cookie retornado automaticamente
4. Requests via Inertia incluem cookie
```

## Fluxo de Login Mobile

```
1. POST /api/v1/auth/login
   Body: { email, password, deviceId, deviceName, platform }
   Response: {
     data: {
       token: "1|abc123...",
       refreshToken: "rt_xyz789...",
       expiresAt: "2026-02-07T14:00:00Z",
       user: { id, name, email, role, tenantId }
     }
   }

2. POST /api/v1/devices/register
   Headers: Authorization: Bearer {token}
   Body: { deviceId, platform, pushToken, appVersion }

3. Todas as requests subsequentes:
   Authorization: Bearer {token}
```

## Refresh Token

```
POST /api/v1/auth/refresh
Headers: Authorization: Bearer {refreshToken}
Response: {
  data: {
    token: "1|newtoken...",
    refreshToken: "rt_newrefresh...",
    expiresAt: "2026-02-08T14:00:00Z"
  }
}
```

- Refresh token e rotacionado a cada uso
- Token antigo e revogado
- Se refresh falhar: redirecionar para login

## Login Biometrico (Mobile)

```
1. Primeiro login com email/senha
2. POST /api/v1/auth/biometric/enroll
   Body: { biometricToken: "device_generated_token" }

3. Logins subsequentes:
   POST /api/v1/auth/biometric/verify
   Body: { deviceId, biometricToken }
```

## Logout

```
POST /api/v1/auth/logout
→ Revoga token atual

POST /api/v1/auth/logout-all
→ Revoga todos os tokens do usuario

DELETE /api/v1/devices/{deviceId}
→ Revoga tokens de device especifico
```

## Forgot Password

```
POST /api/v1/auth/forgot-password
Body: { email }
→ Envia email com link de reset

POST /api/v1/auth/reset-password
Body: { token, email, password, password_confirmation }
```

## Roles e Permissoes

| Role | Permissoes |
|------|-----------|
| super_admin | Tudo |
| admin | CRUD cameras, usuarios, config do tenant |
| operator | View cameras, live, playback, PTZ |
| viewer | View cameras, live only |

```json
// Response do login inclui permissoes
{
  "user": {
    "role": "admin",
    "permissions": [
      "cameras.view", "cameras.create", "cameras.edit", "cameras.delete",
      "streams.view", "streams.manage",
      "recordings.view", "recordings.export",
      "users.view", "users.manage",
      "settings.manage"
    ]
  }
}
```

## Seguranca

- Senhas: bcrypt com cost 12
- Tokens: 80 caracteres aleatorios
- Rate limit login: 5 tentativas / 15 min
- Bloqueio de conta: apos 10 tentativas / 1h
- Logs de acesso: IP, user agent, timestamp
- CSRF: obrigatorio para web
