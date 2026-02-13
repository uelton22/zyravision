# API — Autenticacao e Seguranca

## Fluxo de Autenticacao

```
┌─────────┐     POST /auth/login      ┌─────────┐
│  Mobile  │ ──────────────────────── → │  Laravel │
│   App    │ ← ──────────────────────  │ Sanctum  │
│          │     { token, user }        │          │
└────┬─────┘                            └─────────┘
     │
     │ Salva token em SecureStorage
     │
     ├─── API calls com Bearer token
     │    Authorization: Bearer {token}
     │
     ├─── WebSocket com token
     │    Pusher auth endpoint
     │
     └─── Streaming com Basic Auth
          /hls/ e /webrtc/ (MediaMTX)
```

## Token Management

### Access Token (Sanctum)
- Tipo: Personal Access Token (Sanctum)
- Formato: `{id}|{hash}` (ex: `1|abc123def456...`)
- Expiracao: 24 horas
- Armazenamento: `flutter_secure_storage` (Keychain/Keystore)
- Renovacao: POST /auth/refresh antes de expirar

### Interceptor Dio (Auto-refresh)

```dart
class AuthInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    final token = authService.accessToken;
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (err.response?.statusCode == 401) {
      // Tenta refresh
      final refreshed = await authService.refreshToken();
      if (refreshed) {
        // Retry request original com novo token
        final retryResponse = await dio.fetch(err.requestOptions);
        handler.resolve(retryResponse);
        return;
      }
      // Refresh falhou — logout
      authService.logout();
      router.go('/login');
    }
    handler.next(err);
  }
}
```

### Token Lifecycle

```
Login
  → Recebe token
    → Salva SecureStorage
      → Usa em todas requests
        → Token proximo de expirar (< 2h)?
          → POST /auth/refresh
            → Novo token
              → Atualiza SecureStorage
        → Token expirou?
          → 401 Unauthorized
            → Tenta refresh
              → Sucesso? Retry request
              → Falha? Logout + tela login
```

## Biometria

### Fluxo

```
1. Primeiro login: email + senha → sucesso
2. App pergunta: "Deseja usar biometria?"
3. Se sim: salva credenciais cifradas com biometric key
4. Proximos acessos: biometria → descriptografa → auto-login
```

### Implementacao

```dart
class BiometricService {
  final LocalAuthentication _localAuth;
  final FlutterSecureStorage _storage;

  Future<bool> isAvailable() async {
    return await _localAuth.canCheckBiometrics;
  }

  Future<bool> authenticate() async {
    return await _localAuth.authenticate(
      localizedReason: 'Acesse o ZyraVision',
      options: const AuthenticationOptions(
        stickyAuth: true,
        biometricOnly: true,
      ),
    );
  }

  Future<void> saveCredentials(String email, String password) async {
    await _storage.write(key: 'bio_email', value: email);
    await _storage.write(key: 'bio_password', value: password);
  }

  Future<Map<String, String>?> getCredentials() async {
    final email = await _storage.read(key: 'bio_email');
    final password = await _storage.read(key: 'bio_password');
    if (email != null && password != null) {
      return {'email': email, 'password': password};
    }
    return null;
  }
}
```

## Autenticacao Streaming

### MediaMTX Basic Auth

O MediaMTX usa autenticacao Basic Auth separada do Sanctum.

```dart
class StreamingAuthService {
  // Credenciais fixas configuradas no MediaMTX
  // Em producao, usar endpoint do backend para obter credenciais temporarias
  String get basicAuth {
    final credentials = base64Encode(utf8.encode('zyravision:zyravision_api_secret'));
    return 'Basic $credentials';
  }

  Map<String, String> get headers => {
    'Authorization': basicAuth,
  };
}
```

### HLS com Auth

```dart
// better_player config com auth
BetterPlayerDataSource(
  BetterPlayerDataSourceType.network,
  hlsUrl,
  headers: {
    'Authorization': streamingAuthService.basicAuth,
  },
)
```

### WebRTC com Auth

```dart
// WHEP request com auth
final response = await dio.post(
  '/webrtc/$path/whep',
  data: sdpOffer,
  options: Options(
    headers: {
      'Authorization': streamingAuthService.basicAuth,
      'Content-Type': 'application/sdp',
    },
  ),
);
```

## WebSocket Auth (Reverb/Pusher)

```dart
final pusher = PusherChannelsFlutter.getInstance();

await pusher.init(
  apiKey: env.pusherKey,
  cluster: env.pusherCluster,
  onAuthorizer: (channelName, socketId, options) async {
    // Chama endpoint de auth do Laravel
    final response = await dio.post('/broadcasting/auth', data: {
      'socket_id': socketId,
      'channel_name': channelName,
    });
    return response.data;
  },
);
```

## Secure Storage Keys

| Key | Descricao | Quando salva |
|-----|-----------|-------------|
| `access_token` | Token Sanctum | Login/refresh |
| `token_expires_at` | Expiracao do token | Login/refresh |
| `user_id` | UUID do usuario | Login |
| `tenant_id` | UUID do tenant | Login |
| `bio_email` | Email (biometria) | Ativacao biometria |
| `bio_password` | Senha (biometria) | Ativacao biometria |
| `bio_enabled` | Flag biometria | Toggle config |

## Seguranca

### Certificate Pinning

```dart
class CertificatePinningInterceptor extends Interceptor {
  // SHA256 hash do certificado do servidor
  final List<String> pinnedCertificates;

  @override
  void onRequest(RequestOptions options, handler) {
    // Validar certificado antes de enviar
    // Implementar via SecurityContext ou dio_http2_adapter
  }
}
```

### Protecoes

| Protecao | Implementacao |
|----------|--------------|
| Token storage | flutter_secure_storage (Keychain/Keystore) |
| Certificate pinning | SHA256 pin no Dio |
| TLS | Minimo TLS 1.2, preferencia 1.3 |
| Jailbreak detection | flutter_jailbreak_detection |
| Screen capture | FLAG_SECURE (Android), UIScreen (iOS) |
| Clipboard | Limpar dados sensiveis apos 30s |
| Background screenshot | Blur overlay no app switcher |
| Debug detection | Desabilitar em release builds |
