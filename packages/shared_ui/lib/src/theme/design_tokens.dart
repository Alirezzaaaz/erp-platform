import 'package:flutter/material.dart';

class DesignTokens {
  static const Color defaultPrimary = Color(0xFF1976D2);
  static const Color defaultSecondary = Color(0xFF424242);
  static const Color success = Color(0xFF2E7D32);
  static const Color warning = Color(0xFFED6C02);
  static const Color error = Color(0xFFD32F2F);
  static const Color background = Color(0xFFF5F5F5);

  static const double radiusSm = 8.0;
  static const double radiusMd = 12.0;
  static const double radiusLg = 16.0;

  static const double spaceXs = 4.0;
  static const double spaceSm = 8.0;
  static const double spaceMd = 16.0;
  static const double spaceLg = 24.0;
  static const double spaceXl = 32.0;

  static const String fontVazir = 'Vazirmatn';
  static const String fontIRANSans = 'IRANSans';

  static Color hexToColor(String hex) {
    hex = hex.replaceFirst('#', '');
    if (hex.length == 6) hex = 'FF$hex';
    return Color(int.parse(hex, radix: 16));
  }
}
