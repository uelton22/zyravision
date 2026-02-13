import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/datasources/remote/auth_remote_datasource.dart';
import '../../data/mappers/user_mapper.dart';
import '../../domain/entities/user.dart';
import '../network/api_exceptions.dart';
import '../storage/secure_storage_service.dart';

final authServiceProvider = Provider<AuthService>((ref) {
  return AuthService(
    ref.read(authRemoteDataSourceProvider),
    ref.read(secureStorageProvider),
  );
});

class AuthService {
  final AuthRemoteDataSource _remote;
  final SecureStorageService _storage;

  AuthService(this._remote, this._storage);

  Future<User> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    final response = await _remote.login(
      email: email,
      password: password,
      deviceName: deviceName,
    );

    await _storage.saveToken(response.token);
    await _storage.saveUserId(response.user.id);
    if (response.user.tenantId != null) {
      await _storage.saveTenantId(response.user.tenantId!);
    }

    return UserMapper.toEntity(response.user);
  }

  Future<void> logout() async {
    try {
      await _remote.logout();
    } catch (_) {
      // Ignore logout errors — clear local state regardless
    }
    await _storage.clearAll();
  }

  Future<User?> getCurrentUser() async {
    final token = await _storage.getToken();
    if (token == null) return null;

    try {
      final response = await _remote.me();
      return UserMapper.toEntity(response.user);
    } on ApiException catch (e) {
      if (e.statusCode == 401 || e.statusCode == 403) {
        // Token invalido ou usuario sem permissao — limpa dados locais
        await _storage.clearAll();
        return null;
      }
      rethrow;
    } on NetworkException {
      // Sem conexao — nao limpa token, pode tentar novamente depois
      return null;
    }
  }

  Future<bool> isAuthenticated() async {
    final token = await _storage.getToken();
    return token != null;
  }
}
