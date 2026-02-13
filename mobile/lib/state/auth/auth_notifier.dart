import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_exceptions.dart';
import '../../domain/entities/user.dart';
import 'auth_providers.dart';

enum AuthStatus { initial, loading, authenticated, unauthenticated, error }

class AuthNotifierState {
  final AuthStatus status;
  final User? user;
  final String? errorMessage;

  const AuthNotifierState({
    this.status = AuthStatus.initial,
    this.user,
    this.errorMessage,
  });

  bool get isAuthenticated => status == AuthStatus.authenticated;
  bool get isLoading => status == AuthStatus.loading;

  AuthNotifierState copyWith({
    AuthStatus? status,
    User? user,
    String? errorMessage,
  }) {
    return AuthNotifierState(
      status: status ?? this.status,
      user: user ?? this.user,
      errorMessage: errorMessage,
    );
  }
}

class AuthNotifier extends Notifier<AuthNotifierState> {
  @override
  AuthNotifierState build() {
    return const AuthNotifierState();
  }

  Future<void> checkAuth() async {
    state = state.copyWith(status: AuthStatus.loading);

    try {
      final user = await ref.read(getCurrentUserUseCaseProvider).call();
      if (user != null) {
        state = AuthNotifierState(
          status: AuthStatus.authenticated,
          user: user,
        );
      } else {
        state = const AuthNotifierState(status: AuthStatus.unauthenticated);
      }
    } catch (_) {
      state = const AuthNotifierState(status: AuthStatus.unauthenticated);
    }
  }

  Future<bool> login({
    required String email,
    required String password,
  }) async {
    state = state.copyWith(status: AuthStatus.loading, errorMessage: null);

    try {
      final user = await ref.read(loginUseCaseProvider).call(
            email: email,
            password: password,
            deviceName: 'ZyraVision Mobile',
          );

      state = AuthNotifierState(
        status: AuthStatus.authenticated,
        user: user,
      );
      return true;
    } on ValidationException catch (e) {
      state = AuthNotifierState(
        status: AuthStatus.error,
        errorMessage: e.getAllErrors().first,
      );
      return false;
    } on ApiException catch (e) {
      state = AuthNotifierState(
        status: AuthStatus.error,
        errorMessage: e.statusCode == 401
            ? 'Email ou senha incorretos'
            : e.message,
      );
      return false;
    } on NetworkException {
      state = const AuthNotifierState(
        status: AuthStatus.error,
        errorMessage: 'Sem conexao com a internet',
      );
      return false;
    } catch (e) {
      state = const AuthNotifierState(
        status: AuthStatus.error,
        errorMessage: 'Erro inesperado. Tente novamente.',
      );
      return false;
    }
  }

  Future<void> logout() async {
    await ref.read(logoutUseCaseProvider).call();
    state = const AuthNotifierState(status: AuthStatus.unauthenticated);
  }
}
