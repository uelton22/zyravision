import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/auth/auth_service.dart';
import '../../domain/entities/user.dart';
import '../../domain/repositories/auth_repository.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepositoryImpl(ref.read(authServiceProvider));
});

class AuthRepositoryImpl implements AuthRepository {
  final AuthService _authService;

  AuthRepositoryImpl(this._authService);

  @override
  Future<User> login({
    required String email,
    required String password,
    required String deviceName,
  }) {
    return _authService.login(
      email: email,
      password: password,
      deviceName: deviceName,
    );
  }

  @override
  Future<void> logout() => _authService.logout();

  @override
  Future<User?> getCurrentUser() => _authService.getCurrentUser();

  @override
  Future<bool> isAuthenticated() => _authService.isAuthenticated();
}
