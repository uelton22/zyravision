# Design System — Light Theme

## ThemeData Configuration

```dart
ThemeData lightTheme() {
  return ThemeData(
    useMaterial3: true,
    brightness: Brightness.light,

    // Colors
    colorScheme: const ColorScheme.light(
      primary: Color(0xFF2563EB),
      onPrimary: Color(0xFFFFFFFF),
      primaryContainer: Color(0xFFDBEAFE),
      secondary: Color(0xFF64748B),
      onSecondary: Color(0xFFFFFFFF),
      surface: Color(0xFFF8FAFC),
      onSurface: Color(0xFF0F172A),
      error: Color(0xFFDC2626),
      onError: Color(0xFFFFFFFF),
      outline: Color(0xFFE2E8F0),
    ),

    // Scaffold
    scaffoldBackgroundColor: const Color(0xFFFFFFFF),

    // AppBar
    appBarTheme: const AppBarTheme(
      backgroundColor: Color(0xFFF8FAFC),
      foregroundColor: Color(0xFF0F172A),
      elevation: 0,
      scrolledUnderElevation: 1,
      centerTitle: false,
    ),

    // Bottom Navigation
    bottomNavigationBarTheme: const BottomNavigationBarThemeData(
      backgroundColor: Color(0xFFF8FAFC),
      selectedItemColor: Color(0xFF2563EB),
      unselectedItemColor: Color(0xFF94A3B8),
      type: BottomNavigationBarType.fixed,
      elevation: 8,
    ),

    // Cards
    cardTheme: CardThemeData(
      color: const Color(0xFFF8FAFC),
      elevation: 1,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
    ),

    // Inputs
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: const Color(0xFFFFFFFF),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFF2563EB), width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFFDC2626), width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      hintStyle: const TextStyle(color: Color(0xFF94A3B8)),
      labelStyle: const TextStyle(color: Color(0xFF64748B)),
    ),

    // Divider
    dividerTheme: const DividerThemeData(
      color: Color(0xFFE2E8F0),
      thickness: 1,
    ),

    // Switch
    switchTheme: SwitchThemeData(
      thumbColor: WidgetStateProperty.resolveWith((states) {
        if (states.contains(WidgetState.selected)) return const Color(0xFF2563EB);
        return const Color(0xFF94A3B8);
      }),
      trackColor: WidgetStateProperty.resolveWith((states) {
        if (states.contains(WidgetState.selected)) return const Color(0xFFDBEAFE);
        return const Color(0xFFE2E8F0);
      }),
    ),

    // Text
    textTheme: GoogleFonts.interTextTheme(),
  );
}
```

## Paleta Visual

| Elemento | Cor | Hex |
|----------|-----|-----|
| Background | Branco puro | #FFFFFF |
| Surface | Cinza muito claro | #F8FAFC |
| Surface Variant | Cinza claro | #F1F5F9 |
| Border | Cinza medio | #E2E8F0 |
| Text Primary | Quase preto | #0F172A |
| Text Secondary | Cinza | #64748B |
| Text Tertiary | Cinza claro | #94A3B8 |
| Primary | Azul | #2563EB |
| Success | Verde | #16A34A |
| Warning | Amarelo | #D97706 |
| Danger | Vermelho | #DC2626 |
