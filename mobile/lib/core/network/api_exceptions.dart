abstract class AppException implements Exception {
  final String message;
  final String? code;
  final dynamic originalError;

  const AppException(this.message, {this.code, this.originalError});

  @override
  String toString() => '$runtimeType($code): $message';
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
    required Map<String, List<String>> super.fieldErrors,
  }) : super(statusCode: 422);

  String? getFieldError(String field) {
    return fieldErrors?[field]?.first;
  }

  List<String> getAllErrors() {
    return fieldErrors?.values.expand((e) => e).toList() ?? [message];
  }
}
