import 'package:flutter/material.dart';

class ThemeConfig {
  final int version;
  final ThemeColors theme;
  final LayoutConfig layout;

  ThemeConfig({
    required this.version,
    required this.theme,
    required this.layout,
  });

  factory ThemeConfig.fromJson(Map<String, dynamic> json) {
    return ThemeConfig(
      version: json['version'] ?? 1,
      theme: ThemeColors.fromJson(json['theme'] ?? {}),
      layout: LayoutConfig.fromJson(json['layout'] ?? {}),
    );
  }

  factory ThemeConfig.defaultConfig() => ThemeConfig(
        version: 0,
        theme: ThemeColors.defaults(),
        layout: LayoutConfig.defaults(),
      );
}

class ThemeColors {
  final String primaryColor;
  final String secondaryColor;
  final String successColor;
  final String warningColor;
  final String errorColor;
  final String backgroundColor;
  final String fontFamily;
  final int borderRadius;
  final String? logoUrl;
  final String themeMode;

  ThemeColors({
    required this.primaryColor,
    required this.secondaryColor,
    required this.successColor,
    required this.warningColor,
    required this.errorColor,
    required this.backgroundColor,
    required this.fontFamily,
    required this.borderRadius,
    this.logoUrl,
    required this.themeMode,
  });

  factory ThemeColors.fromJson(Map<String, dynamic> json) => ThemeColors(
        primaryColor: json['primaryColor'] ?? '#1976D2',
        secondaryColor: json['secondaryColor'] ?? '#424242',
        successColor: json['successColor'] ?? '#2E7D32',
        warningColor: json['warningColor'] ?? '#ED6C02',
        errorColor: json['errorColor'] ?? '#D32F2F',
        backgroundColor: json['backgroundColor'] ?? '#F5F5F5',
        fontFamily: json['fontFamily'] ?? 'Vazirmatn',
        borderRadius: json['borderRadius'] ?? 8,
        logoUrl: json['logoUrl'] as String?,
        themeMode: json['themeMode'] ?? 'light',
      );

  factory ThemeColors.defaults() => ThemeColors(
        primaryColor: '#1976D2',
        secondaryColor: '#424242',
        successColor: '#2E7D32',
        warningColor: '#ED6C02',
        errorColor: '#D32F2F',
        backgroundColor: '#F5F5F5',
        fontFamily: 'Vazirmatn',
        borderRadius: 8,
        themeMode: 'light',
      );

  Color get primary => _hex(primaryColor);
  Color get secondary => _hex(secondaryColor);
  Color get success => _hex(successColor);
  Color get warning => _hex(warningColor);
  Color get error => _hex(errorColor);
  Color get background => _hex(backgroundColor);

  bool get isDark => themeMode == 'dark';

  Color _hex(String hex) {
    hex = hex.replaceFirst('#', '');
    if (hex.length == 6) hex = 'FF$hex';
    return Color(int.parse(hex, radix: 16));
  }
}

class LayoutConfig {
  final List<SidebarItem> sidebar;
  final List<DashboardWidget> dashboardWidgets;

  LayoutConfig({
    required this.sidebar,
    required this.dashboardWidgets,
  });

  factory LayoutConfig.fromJson(Map<String, dynamic> json) => LayoutConfig(
        sidebar: (json['sidebar'] as List<dynamic>?)
                ?.map((e) => SidebarItem.fromJson(e))
                .toList() ??
            SidebarItem.defaults(),
        dashboardWidgets: (json['dashboard_widgets'] as List<dynamic>?)
                ?.map((e) => DashboardWidget.fromJson(e))
                .toList() ??
            [],
      );

  factory LayoutConfig.defaults() => LayoutConfig(
        sidebar: SidebarItem.defaults(),
        dashboardWidgets: [],
      );
}

class SidebarItem {
  final String key;
  final String label;
  final String icon;

  SidebarItem({required this.key, required this.label, required this.icon});

  factory SidebarItem.fromJson(Map<String, dynamic> json) => SidebarItem(
        key: json['key'] as String,
        label: json['label'] as String,
        icon: json['icon'] as String,
      );

  static List<SidebarItem> defaults() => [
        SidebarItem(key: 'dashboard', label: 'داشبورد', icon: 'home'),
        SidebarItem(key: 'products', label: 'کالاها', icon: 'package'),
        SidebarItem(key: 'inventory', label: 'انبار', icon: 'warehouse'),
        SidebarItem(key: 'invoices', label: 'فاکتورها', icon: 'file-text'),
        SidebarItem(key: 'parties', label: 'اشخاص', icon: 'users'),
        SidebarItem(key: 'accounting', label: 'حسابداری', icon: 'calculator'),
        SidebarItem(key: 'reports', label: 'گزارش‌ها', icon: 'chart-bar'),
        SidebarItem(key: 'settings', label: 'تنظیمات', icon: 'settings'),
      ];
}

class DashboardWidget {
  final String type;
  final String title;
  final String valueKey;
  final String color;

  DashboardWidget({
    required this.type,
    required this.title,
    required this.valueKey,
    required this.color,
  });

  factory DashboardWidget.fromJson(Map<String, dynamic> json) => DashboardWidget(
        type: json['type'] as String,
        title: json['title'] as String,
        valueKey: json['value_key'] as String,
        color: json['color'] as String? ?? 'primary',
      );
}
