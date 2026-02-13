import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../state/auth/auth_providers.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  bool _checking = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkAuth();
    });
  }

  Future<void> _checkAuth() async {
    // Evita multiplas execucoes
    if (_checking) return;
    _checking = true;

    debugPrint('[Splash] Verificando autenticacao...');

    try {
      await ref.read(authNotifierProvider.notifier).checkAuth();
      debugPrint('[Splash] checkAuth concluido.');
    } catch (e) {
      debugPrint('[Splash] checkAuth falhou: $e');
    }

    // O GoRouter vai redirecionar automaticamente via refreshListenable
    // quando o authState mudar de loading → authenticated/unauthenticated
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: ZyColors.primary,
                borderRadius: ZySpacing.borderRadiusXl,
              ),
              child: const Icon(
                Icons.videocam,
                size: 40,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: ZySpacing.base),
            Text(
              'ZyraVision',
              style: Theme.of(context).textTheme.displayMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                    color: ZyColors.primary,
                  ),
            ),
            const SizedBox(height: ZySpacing.xxl),
            const SizedBox(
              width: 24,
              height: 24,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          ],
        ),
      ),
    );
  }
}
