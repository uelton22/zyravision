import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../config/constants.dart';

final sharedPreferencesProvider = Provider<SharedPreferences>((ref) {
  throw UnimplementedError('SharedPreferences must be overridden in main.dart');
});

final preferencesServiceProvider = Provider<PreferencesService>((ref) {
  final prefs = ref.read(sharedPreferencesProvider);
  return PreferencesService(prefs);
});

class PreferencesService {
  final SharedPreferences _prefs;

  PreferencesService(this._prefs);

  // Theme
  ThemeMode getThemeMode() {
    final value = _prefs.getString(StorageKeys.themeMode);
    switch (value) {
      case 'dark':
        return ThemeMode.dark;
      case 'light':
        return ThemeMode.light;
      default:
        return ThemeMode.system;
    }
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    await _prefs.setString(StorageKeys.themeMode, mode.name);
  }

  // Onboarding
  bool isOnboardingDone() {
    return _prefs.getBool(StorageKeys.onboardingDone) ?? false;
  }

  Future<void> setOnboardingDone() async {
    await _prefs.setBool(StorageKeys.onboardingDone, true);
  }
}
