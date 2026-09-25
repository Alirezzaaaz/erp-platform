import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../models/theme_config.dart';

class AppTheme {
  static ThemeData fromConfig(ThemeConfig config) {
    final c = config.theme;

    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: c.primary,
        brightness: c.isDark ? Brightness.dark : Brightness.light,
        primary: c.primary,
        secondary: c.secondary,
        error: c.error,
      ),
      scaffoldBackgroundColor: c.background,
      textTheme: GoogleFonts.vazirmatnTextTheme(),
      fontFamily: 'Vazirmatn',
      cardTheme: CardThemeData(
        elevation: 1,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(c.borderRadius.toDouble()),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: c.isDark ? Colors.grey.shade900 : Colors.grey.shade50,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(c.borderRadius.toDouble()),
          borderSide: BorderSide.none,
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(c.borderRadius.toDouble()),
          ),
        ),
      ),
    );
  }
}
