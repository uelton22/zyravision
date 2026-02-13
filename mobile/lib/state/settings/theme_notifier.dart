import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/storage/preferences_service.dart';

class ThemeNotifier extends Notifier<ThemeMode> {
  @override
  ThemeMode build() {
    final prefs = ref.read(preferencesServiceProvider);
    return prefs.getThemeMode();
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    final prefs = ref.read(preferencesServiceProvider);
    await prefs.setThemeMode(mode);
    state = mode;
  }

  Future<void> toggle() async {
    final newMode =
        state == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark;
    await setThemeMode(newMode);
  }
}
