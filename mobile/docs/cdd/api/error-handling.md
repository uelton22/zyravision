# API — Tratamento de Erros

## Hierarquia de Exceptions

```
AppException (base)
├── NetworkException
│   ├── NoInternetException
│   ├── TimeoutException
│   └── ServerUnreachableException
├── ApiException
│   ├── UnauthorizedException (401)
│   ├── ForbiddenException (403)
│   ├── NotFoundException (404)
│   ├── ValidationException (422)
│   ├── RateLimitException (429)
│   ├── ServerException (500)
│   └── MaintenanceException (503)
├── StreamingException
│   ├── StreamNotAvailableException
│   ├── StreamTimeoutException
│   └── StreamCodecException
└── StorageException
    ├── CacheReadException
    └── CacheWriteException
```

## Classe Base

```dart
// lib/core/network/exceptions.dart
abstract class AppException implements Exception {
  final String message;
  final String? code;
  final dynamic originalError;

  const AppException(this.message, {this.code, this.originalError});

  @override
  String toString() => 'AppException($code): $message';
}

class NetworkException extends AppException {
  const NetworkException(super.message, {super.code, super.originalError});
}

class ApiException extends AppException {
  final int statusCode;
  final Map<String, List<String>>? fieldErrors;

  const ApiException(
    super.message, {
    required this.statusCode,
    this.fieldErrors,
    super.code,
    super.originalError,
  });
}

class ValidationException extends ApiException {
  const ValidationException(
    super.message, {
    required super.fieldErrors,
  }) : super(statusCode: 422);

  String? getFieldError(String field) {
    return fieldErrors?[field]?.first;
  }

  List<String> getAllErrors() {
    return fieldErrors?.values.expand((e) => e).toList() ?? [message];
  }
}
```

## Interceptor de Erros (Dio)

```dart
// lib/core/network/error_interceptor.dart
class ErrorInterceptor extends Interceptor {
  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    final exception = _mapException(err);
    handler.reject(DioException(
      requestOptions: err.requestOptions,
      error: exception,
      type: err.type,
      response: err.response,
    ));
  }

  AppException _mapException(DioException err) {
    // Sem conexao
    if (err.type == DioExceptionType.connectionError) {
      return const NetworkException(
        'Sem conexao com a internet',
        code: 'NO_INTERNET',
      );
    }

    // Timeout
    if (err.type == DioExceptionType.connectionTimeout ||
        err.type == DioExceptionType.receiveTimeout ||
        err.type == DioExceptionType.sendTimeout) {
      return const NetworkException(
        'Tempo de conexao esgotado',
        code: 'TIMEOUT',
      );
    }

    // Response de erro
    final response = err.response;
    if (response == null) {
      return NetworkException(
        'Servidor indisponivel',
        code: 'UNREACHABLE',
        originalError: err,
      );
    }

    final data = response.data;
    final message = data is Map ? data['message'] as String? : null;

    switch (response.statusCode) {
      case 401:
        return ApiException(
          message ?? 'Sessao expirada. Faca login novamente.',
          statusCode: 401,
          code: 'UNAUTHORIZED',
        );

      case 403:
        return ApiException(
          message ?? 'Voce nao tem permissao para esta acao.',
          statusCode: 403,
          code: 'FORBIDDEN',
        );

      case 404:
        return ApiException(
          message ?? 'Recurso nao encontrado.',
          statusCode: 404,
          code: 'NOT_FOUND',
        );

      case 422:
        final errors = _parseFieldErrors(data);
        return ValidationException(
          message ?? 'Dados invalidos.',
          fieldErrors: errors,
        );

      case 429:
        return ApiException(
          message ?? 'Muitas requisicoes. Tente novamente em instantes.',
          statusCode: 429,
          code: 'RATE_LIMIT',
        );

      case 503:
        return ApiException(
          message ?? 'Sistema em manutencao. Tente novamente em breve.',
          statusCode: 503,
          code: 'MAINTENANCE',
        );

      default:
        return ApiException(
          message ?? 'Erro inesperado. Tente novamente.',
          statusCode: response.statusCode ?? 500,
          code: 'SERVER_ERROR',
          originalError: err,
        );
    }
  }

  Map<String, List<String>>? _parseFieldErrors(dynamic data) {
    if (data is! Map) return null;
    final errors = data['errors'];
    if (errors is! Map) return null;

    return errors.map((key, value) => MapEntry(
      key as String,
      (value as List).cast<String>(),
    ));
  }
}
```

## Tratamento no Repository

```dart
// lib/data/repositories/camera_repository_impl.dart
class CameraRepositoryImpl implements CameraRepository {
  final CameraRemoteDataSource _remote;
  final CameraLocalDataSource _local;

  @override
  Future<PaginatedResult<Camera>> getCameras({
    int page = 1,
    String? search,
  }) async {
    try {
      final response = await _remote.getCameras(page: page, search: search);
      final cameras = response.data.map(CameraMapper.toEntity).toList();

      // Cache para uso offline
      if (page == 1 && search == null) {
        await _local.cacheCameras(cameras);
      }

      return PaginatedResult(data: cameras, meta: response.meta);
    } on NetworkException {
      // Sem rede — retorna cache
      final cached = await _local.getCachedCameras();
      return PaginatedResult(
        data: cached,
        meta: PaginationMeta.offline(),
        isOffline: true,
      );
    } on ApiException {
      // Erro de API — propaga para o state
      rethrow;
    }
  }
}
```

## Tratamento no State (Riverpod)

```dart
// lib/state/cameras/camera_list_notifier.dart
class CameraListNotifier extends AutoDisposeAsyncNotifier<PaginatedResult<Camera>> {
  @override
  Future<PaginatedResult<Camera>> build() async {
    final useCase = ref.read(getCamerasUseCaseProvider);
    return useCase(page: 1);
    // Erros sao capturados automaticamente pelo AsyncValue
  }

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(getCamerasUseCaseProvider)(page: 1),
    );
  }
}
```

## Tratamento na UI

```dart
// lib/ui/screens/cameras/camera_list_screen.dart
camerasAsync.when(
  data: (result) {
    if (result.isOffline) {
      // Mostra banner offline
      return Column(children: [
        const ZyOfflineBanner(),
        _buildGrid(result.data),
      ]);
    }
    return _buildGrid(result.data);
  },
  loading: () => const ZySkeleton.cameraGrid(),
  error: (error, stack) {
    if (error is ApiException && error.statusCode == 403) {
      return const ZyEmptyState(
        icon: Icons.lock_outlined,
        message: 'Sem permissao',
        description: 'Voce nao tem acesso a esta funcionalidade.',
      );
    }
    return ZyEmptyState(
      icon: Icons.error_outline,
      message: 'Erro ao carregar',
      description: error is AppException ? error.message : 'Tente novamente.',
      actionLabel: 'Tentar novamente',
      onAction: () => ref.read(cameraListProvider.notifier).refresh(),
    );
  },
);
```

## Retry com Backoff

```dart
// lib/core/network/retry_interceptor.dart
class RetryInterceptor extends Interceptor {
  final int maxRetries;
  final Duration initialDelay;

  RetryInterceptor({
    this.maxRetries = 3,
    this.initialDelay = const Duration(seconds: 1),
  });

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    final retryCount = err.requestOptions.extra['retryCount'] as int? ?? 0;

    // Retry apenas para erros de rede e 5xx
    final shouldRetry = retryCount < maxRetries &&
        (err.type == DioExceptionType.connectionError ||
         err.type == DioExceptionType.connectionTimeout ||
         (err.response?.statusCode ?? 0) >= 500);

    if (shouldRetry) {
      final delay = initialDelay * pow(2, retryCount);
      await Future.delayed(delay);

      err.requestOptions.extra['retryCount'] = retryCount + 1;

      try {
        final response = await Dio().fetch(err.requestOptions);
        handler.resolve(response);
        return;
      } catch (_) {}
    }

    handler.next(err);
  }
}
```

## Mensagens de Erro (PT-BR)

| Codigo | Mensagem para Usuario |
|--------|----------------------|
| NO_INTERNET | Sem conexao com a internet. Verifique sua rede. |
| TIMEOUT | A requisicao demorou muito. Tente novamente. |
| UNREACHABLE | Nao foi possivel conectar ao servidor. |
| UNAUTHORIZED | Sessao expirada. Faca login novamente. |
| FORBIDDEN | Voce nao tem permissao para esta acao. |
| NOT_FOUND | O recurso solicitado nao foi encontrado. |
| RATE_LIMIT | Muitas requisicoes. Aguarde alguns segundos. |
| MAINTENANCE | Sistema em manutencao. Tente mais tarde. |
| SERVER_ERROR | Erro inesperado. Tente novamente. |
| VALIDATION | Verifique os dados informados. |

## Logging de Erros

```dart
// lib/core/utils/error_logger.dart
class ErrorLogger {
  static void log(AppException error, {StackTrace? stackTrace}) {
    // Em debug: print no console
    if (kDebugMode) {
      debugPrint('ERROR [${error.code}]: ${error.message}');
      if (stackTrace != null) debugPrint(stackTrace.toString());
    }

    // Em release: enviar para Sentry/Crashlytics
    // FirebaseCrashlytics.instance.recordError(error, stackTrace);
  }
}
```
