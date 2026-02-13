import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/theme/zy_theme.dart';
import 'state/settings/settings_providers.dart';
import 'ui/router/app_router.dart';

class ZyraVisionApp extends ConsumerWidget {
  const ZyraVisionApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    final themeMode = ref.watch(themeNotifierProvider);

    return MaterialApp.router(
      title: 'ZyraVision',
      debugShowCheckedModeBanner: false,
      theme: ZyTheme.light(),
      darkTheme: ZyTheme.dark(),
      themeMode: themeMode,
      routerConfig: router,
    );
  }
}
