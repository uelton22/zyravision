import 'dart:math';

import 'package:dio/dio.dart';

import 'api_exceptions.dart';

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
    if (err.type == DioExceptionType.connectionError ||
        err.type == DioExceptionType.unknown) {
      return const NetworkException(
        'Sem conexao com a internet',
        code: 'NO_INTERNET',
      );
    }

    if (err.type == DioExceptionType.connectionTimeout ||
        err.type == DioExceptionType.receiveTimeout ||
        err.type == DioExceptionType.sendTimeout) {
      return const NetworkException(
        'Tempo de conexao esgotado',
        code: 'TIMEOUT',
      );
    }

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
          fieldErrors: errors ?? {},
        );
      case 429:
        return ApiException(
          message ?? 'Muitas requisicoes. Aguarde alguns segundos.',
          statusCode: 429,
          code: 'RATE_LIMIT',
        );
      case 503:
        return ApiException(
          message ?? 'Sistema em manutencao. Tente mais tarde.',
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
    return errors.map(
      (key, value) => MapEntry(
        key as String,
        (value as List).cast<String>(),
      ),
    );
  }
}

class RetryInterceptor extends Interceptor {
  final int maxRetries;
  final Duration initialDelay;
  final Dio _dio;

  RetryInterceptor(
    this._dio, {
    this.maxRetries = 3,
    this.initialDelay = const Duration(seconds: 1),
  });

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    final retryCount = err.requestOptions.extra['retryCount'] as int? ?? 0;

    final shouldRetry = retryCount < maxRetries &&
        (err.type == DioExceptionType.connectionError ||
            err.type == DioExceptionType.connectionTimeout ||
            (err.response?.statusCode ?? 0) >= 500);

    if (shouldRetry) {
      final delay = initialDelay * pow(2, retryCount);
      await Future.delayed(delay);

      err.requestOptions.extra['retryCount'] = retryCount + 1;

      try {
        final response = await _dio.fetch(err.requestOptions);
        handler.resolve(response);
        return;
      } catch (_) {
        // Fall through to reject
      }
    }

    handler.next(err);
  }
}
