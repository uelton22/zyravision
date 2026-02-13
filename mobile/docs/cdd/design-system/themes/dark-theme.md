# Design System — Dark Theme

## ThemeData Configuration

```dart
ThemeData darkTheme() {
  return ThemeData(
    useMaterial3: true,
    brightness: Brightness.dark,

    // Colors
    colorScheme: const ColorScheme.dark(
      primary: Color(0xFF3B82F6),
      onPrimary: Color(0xFFFFFFFF),
      primaryContainer: Color(0xFF1E3A5F),
      secondary: Color(0xFF94A3B8),
      onSecondary: Color(0xFFFFFFFF),
      surface: Color(0xFF1E293B),
      onSurface: Color(0xFFF8FAFC),
      error: Color(0xFFEF4444),
      onError: Color(0xFFFFFFFF),
      outline: Color(0xFF475569),
    ),

    // Scaffold
    scaffoldBackgroundColor: const Color(0xFF0F172A),

    // AppBar
    appBarTheme: const AppBarTheme(
      backgroundColor: Color(0xFF1E293B),
      foregroundColor: Color(0xFFF8FAFC),
      elevation: 0,
      scrolledUnderElevation: 1,
      centerTitle: false,
    ),

    // Bottom Navigation
    bottomNavigationBarTheme: const BottomNavigationBarThemeData(
      backgroundColor: Color(0xFF1E293B),
      selectedItemColor: Color(0xFF3B82F6),
      unselectedItemColor: Color(0xFF64748B),
      type: BottomNavigationBarType.fixed,
      elevation: 8,
    ),

    // Cards
    cardTheme: CardThemeData(
      color: const Color(0xFF1E293B),
      elevation: 1,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: Color(0xFF475569)),
      ),
    ),

    // Inputs
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: const Color(0xFF1E293B),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFF475569)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFF475569)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFF3B82F6), width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFFEF4444), width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      hintStyle: const TextStyle(color: Color(0xFF64748B)),
      labelStyle: const TextStyle(color: Color(0xFF94A3B8)),
    ),

    // Divider
    dividerTheme: const DividerThemeData(
      color: Color(0xFF475569),
      thickness: 1,
    ),

    // Switch
    switchTheme: SwitchThemeData(
      thumbColor: WidgetStateProperty.resolveWith((states) {
        if (states.contains(WidgetState.selected)) return const Color(0xFF3B82F6);
        return const Color(0xFF64748B);
      }),
      trackColor: WidgetStateProperty.resolveWith((states) {
        if (states.contains(WidgetState.selected)) return const Color(0xFF1E3A5F);
        return const Color(0xFF475569);
      }),
    ),

    // Text
    textTheme: GoogleFonts.interTextTheme(ThemeData.dark().textTheme),
  );
}
```

## Paleta Visual

| Elemento | Cor | Hex |
|----------|-----|-----|
| Background | Azul muito escuro | #0F172A |
| Surface | Azul escuro | #1E293B |
| Surface Variant | Cinza escuro | #334155 |
| Border | Cinza medio | #475569 |
| Text Primary | Branco quase | #F8FAFC |
| Text Secondary | Cinza claro | #94A3B8 |
| Text Tertiary | Cinza medio | #64748B |
| Primary | Azul claro | #3B82F6 |
| Success | Verde claro | #22C55E |
| Warning | Amarelo claro | #F59E0B |
| Danger | Vermelho claro | #EF4444 |

## Nota
O tema dark do mobile segue os mesmos tokens do tema dark do web (Tailwind dark: classes), garantindo consistencia visual cross-platform.
