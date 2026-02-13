import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'zy_colors.dart';
import 'zy_spacing.dart';

class ZyTheme {
  ZyTheme._();

  static ThemeData light() {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      colorScheme: const ColorScheme.light(
        primary: ZyColors.primary,
        onPrimary: ZyColors.onPrimary,
        primaryContainer: ZyColors.primaryLight,
        secondary: ZyColors.textSecondaryLight,
        onSecondary: ZyColors.onPrimary,
        surface: ZyColors.surfaceLight,
        onSurface: ZyColors.textPrimaryLight,
        error: ZyColors.danger,
        onError: ZyColors.onPrimary,
        outline: ZyColors.borderLight,
      ),
      scaffoldBackgroundColor: ZyColors.backgroundLight,
      appBarTheme: const AppBarTheme(
        backgroundColor: ZyColors.surfaceLight,
        foregroundColor: ZyColors.textPrimaryLight,
        elevation: 0,
        scrolledUnderElevation: 1,
        centerTitle: false,
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: ZyColors.surfaceLight,
        selectedItemColor: ZyColors.primary,
        unselectedItemColor: ZyColors.textTertiaryLight,
        type: BottomNavigationBarType.fixed,
        elevation: 8,
      ),
      cardTheme: CardThemeData(
        color: ZyColors.surfaceLight,
        elevation: 1,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusLg),
          side: const BorderSide(color: ZyColors.borderLight),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: ZyColors.backgroundLight,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusMd),
          borderSide: const BorderSide(color: ZyColors.borderLight),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusMd),
          borderSide: const BorderSide(color: ZyColors.borderLight),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusMd),
          borderSide: const BorderSide(color: ZyColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusMd),
          borderSide: const BorderSide(color: ZyColors.danger, width: 2),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusMd),
          borderSide: const BorderSide(color: ZyColors.danger, width: 2),
        ),
        contentPadding: ZySpacing.inputContentPadding,
        hintStyle: const TextStyle(color: ZyColors.textTertiaryLight),
        labelStyle: const TextStyle(color: ZyColors.textSecondaryLight),
      ),
      dividerTheme: const DividerThemeData(
        color: ZyColors.borderLight,
        thickness: 1,
      ),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) return ZyColors.primary;
          return ZyColors.textTertiaryLight;
        }),
        trackColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) return ZyColors.primaryLight;
          return ZyColors.borderLight;
        }),
      ),
      textTheme: GoogleFonts.interTextTheme(),
    );
  }

  static ThemeData dark() {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: const ColorScheme.dark(
        primary: ZyColors.primaryDarkMode,
        onPrimary: ZyColors.onPrimary,
        primaryContainer: ZyColors.primaryLightDarkMode,
        secondary: ZyColors.textSecondaryDark,
        onSecondary: ZyColors.onPrimary,
        surface: ZyColors.surfaceDark,
        onSurface: ZyColors.textPrimaryDark,
        error: ZyColors.dangerDarkMode,
        onError: ZyColors.onPrimary,
        outline: ZyColors.borderDark,
      ),
      scaffoldBackgroundColor: ZyColors.backgroundDark,
      appBarTheme: const AppBarTheme(
        backgroundColor: ZyColors.surfaceDark,
        foregroundColor: ZyColors.textPrimaryDark,
        elevation: 0,
        scrolledUnderElevation: 1,
        centerTitle: false,
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: ZyColors.surfaceDark,
        selectedItemColor: ZyColors.primaryDarkMode,
        unselectedItemColor: ZyColors.textTertiaryDark,
        type: BottomNavigationBarType.fixed,
        elevation: 8,
      ),
      cardTheme: CardThemeData(
        color: ZyColors.surfaceDark,
        elevation: 1,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusLg),
          side: const BorderSide(color: ZyColors.borderDark),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: ZyColors.surfaceDark,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusMd),
          borderSide: const BorderSide(color: ZyColors.borderDark),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusMd),
          borderSide: const BorderSide(color: ZyColors.borderDark),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusMd),
          borderSide: const BorderSide(color: ZyColors.primaryDarkMode, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusMd),
          borderSide: const BorderSide(color: ZyColors.dangerDarkMode, width: 2),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(ZySpacing.radiusMd),
          borderSide: const BorderSide(color: ZyColors.dangerDarkMode, width: 2),
        ),
        contentPadding: ZySpacing.inputContentPadding,
        hintStyle: const TextStyle(color: ZyColors.textTertiaryDark),
        labelStyle: const TextStyle(color: ZyColors.textSecondaryDark),
      ),
      dividerTheme: const DividerThemeData(
        color: ZyColors.borderDark,
        thickness: 1,
      ),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) return ZyColors.primaryDarkMode;
          return ZyColors.textTertiaryDark;
        }),
        trackColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) return ZyColors.primaryLightDarkMode;
          return ZyColors.borderDark;
        }),
      ),
      textTheme: GoogleFonts.interTextTheme(ThemeData.dark().textTheme),
    );
  }
}
