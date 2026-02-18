import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../state/auth/auth_notifier.dart';
import '../../state/auth/auth_providers.dart';
import '../screens/auth/login_screen.dart';
import '../screens/cameras/camera_detail_screen.dart';
import '../screens/cameras/camera_list_screen.dart';
import '../screens/cameras/live_view_screen.dart';
import '../screens/cameras/multi_view_screen.dart';
import '../screens/dashboard/dashboard_screen.dart';
import '../screens/splash/splash_screen.dart';
import '../widgets/navigation/zy_bottom_nav.dart';
import 'route_names.dart';

/// Listenable que notifica o GoRouter quando o auth state muda.
/// Isso faz o GoRouter re-avaliar os redirects SEM recriar o router.
class AuthChangeNotifier extends ChangeNotifier {
  AuthChangeNotifier(Ref ref) {
    ref.listen<AuthNotifierState>(authNotifierProvider, (_, __) {
      notifyListeners();
    });
  }
}

final _authChangeNotifierProvider = Provider<AuthChangeNotifier>((ref) {
  return AuthChangeNotifier(ref);
});

/// GoRouter criado UMA UNICA VEZ — nunca é recriado.
/// Usa refreshListenable para re-avaliar redirects quando auth muda.
final routerProvider = Provider<GoRouter>((ref) {
  final authChangeNotifier = ref.read(_authChangeNotifierProvider);

  return GoRouter(
    initialLocation: RouteNames.splash,
    refreshListenable: authChangeNotifier,
    redirect: (context, state) {
      final authState = ref.read(authNotifierProvider);
      final isAuth = authState.isAuthenticated;
      final isLoading = authState.status == AuthStatus.initial ||
          authState.status == AuthStatus.loading;
      final isOnSplash = state.matchedLocation == RouteNames.splash;
      final isOnLogin = state.matchedLocation == RouteNames.login;

      if (isLoading) return null;
      if (!isAuth && !isOnLogin && !isOnSplash) return RouteNames.login;
      if (isAuth && (isOnLogin || isOnSplash)) return RouteNames.dashboard;
      if (!isAuth && isOnSplash) return RouteNames.login;

      return null;
    },
    routes: [
      GoRoute(
        path: RouteNames.splash,
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: RouteNames.login,
        builder: (context, state) => const LoginScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) {
          return _ShellScaffold(child: child);
        },
        routes: [
          GoRoute(
            path: RouteNames.dashboard,
            builder: (context, state) => const DashboardScreen(),
          ),
          GoRoute(
            path: RouteNames.cameras,
            builder: (context, state) => const CameraListScreen(),
            routes: [
              GoRoute(
                path: 'multi-view',
                parentNavigatorKey: null,
                builder: (context, state) {
                  final ids =
                      state.uri.queryParameters['ids']?.split(',') ?? [];
                  return MultiViewScreen(cameraIds: ids);
                },
              ),
              GoRoute(
                path: ':id',
                builder: (context, state) {
                  final id = state.pathParameters['id']!;
                  return CameraDetailScreen(cameraId: id);
                },
                routes: [
                  GoRoute(
                    path: 'live',
                    builder: (context, state) {
                      final id = state.pathParameters['id']!;
                      final cameraName =
                          state.uri.queryParameters['name'] ?? 'Camera';
                      return LiveViewScreen(
                        cameraId: id,
                        cameraName: cameraName,
                      );
                    },
                  ),
                ],
              ),
            ],
          ),
          GoRoute(
            path: RouteNames.alerts,
            builder: (context, state) =>
                const _PlaceholderScreen(title: 'Alertas'),
          ),
          GoRoute(
            path: RouteNames.settings,
            builder: (context, state) =>
                const _PlaceholderScreen(title: 'Configuracoes'),
          ),
        ],
      ),
    ],
  );
});

class _ShellScaffold extends StatelessWidget {
  final Widget child;

  const _ShellScaffold({required this.child});

  static const _routes = [
    RouteNames.dashboard,
    RouteNames.cameras,
    RouteNames.alerts,
    RouteNames.settings,
  ];

  @override
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).matchedLocation;
    final currentIndex = _routes.indexOf(location).clamp(0, 3);

    return Scaffold(
      body: child,
      bottomNavigationBar: ZyBottomNav(
        currentIndex: currentIndex,
        onTap: (index) => context.go(_routes[index]),
      ),
    );
  }
}

class _PlaceholderScreen extends StatelessWidget {
  final String title;

  const _PlaceholderScreen({required this.title});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.construction,
              size: 64,
              color: Theme.of(context).colorScheme.outline,
            ),
            const SizedBox(height: 16),
            Text(
              '$title\nEm desenvolvimento',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyLarge,
            ),
          ],
        ),
      ),
    );
  }
}
