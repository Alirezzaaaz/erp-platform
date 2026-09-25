import 'package:flutter/material.dart';
import 'package:shared_ui/shared_ui.dart';

class AppSidebar extends StatelessWidget {
  final String currentRoute;
  final ValueChanged<String> onNavigate;
  final ThemeConfig? themeConfig;

  const AppSidebar({
    super.key,
    required this.currentRoute,
    required this.onNavigate,
    this.themeConfig,
  });

  @override
  Widget build(BuildContext context) {
    final items = themeConfig?.layout.sidebar ?? SidebarItem.defaults();
    final primary = Theme.of(context).colorScheme.primary;

    return Container(
      width: 240,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        border: Border(
          left: BorderSide(color: Colors.grey.shade200),
        ),
      ),
      child: Column(
        children: [
          // لوگو / عنوان
          Container(
            height: 80,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            alignment: Alignment.centerRight,
            child: Row(
              children: [
                Icon(Icons.inventory_2_outlined, size: 32, color: primary),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'سیستم ERP',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: primary,
                        ),
                  ),
                ),
              ],
            ),
          ),
          Divider(height: 1, color: Colors.grey.shade200),

          // منو
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.symmetric(vertical: 8),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index];
                final isActive = currentRoute == item.key;

                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  child: Material(
                    color: isActive ? primary.withValues(alpha: 0.1) : Colors.transparent,
                    borderRadius: BorderRadius.circular(8),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(8),
                      onTap: () => onNavigate(item.key),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                        child: Row(
                          children: [
                            Icon(
                              _iconFor(item.icon),
                              size: 22,
                              color: isActive ? primary : Colors.grey.shade600,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                item.label,
                                style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: isActive ? FontWeight.w600 : FontWeight.normal,
                                  color: isActive ? primary : Colors.grey.shade800,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          ),

          Divider(height: 1, color: Colors.grey.shade200),
          Padding(
            padding: const EdgeInsets.all(12),
            child: Text(
              'نسخه 0.1.0',
              style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
            ),
          ),
        ],
      ),
    );
  }

  IconData _iconFor(String name) {
    switch (name) {
      case 'home':
      case 'dashboard':
        return Icons.dashboard_outlined;
      case 'package':
      case 'products':
        return Icons.inventory_2_outlined;
      case 'warehouse':
      case 'inventory':
        return Icons.warehouse_outlined;
      case 'map':
      case 'warehouse_map':
        return Icons.map_outlined;
      case 'file-text':
      case 'invoices':
        return Icons.receipt_long_outlined;
      case 'users':
      case 'parties':
        return Icons.people_outline;
      case 'calculator':
      case 'accounting':
        return Icons.account_balance_wallet_outlined;
      case 'chart-bar':
      case 'reports':
        return Icons.bar_chart_outlined;
      case 'settings':
        return Icons.settings_outlined;
      default:
        return Icons.circle_outlined;
    }
  }
}
