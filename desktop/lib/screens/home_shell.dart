import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';
import '../widgets/app_sidebar.dart';
import 'dashboard_screen.dart';
import 'products_screen.dart';
import 'inventory_screen.dart';
import 'parties_screen.dart';
import 'invoices_screen.dart';
import 'accounting_screen.dart';
import 'warehouse_map/layouts_list_screen.dart';
import 'placeholder_screen.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  String _currentRoute = 'dashboard';

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final themeConfig = auth.theme;

    return Scaffold(
      body: Row(
        children: [
          // Sidebar
          AppSidebar(
            currentRoute: _currentRoute,
            onNavigate: (route) => setState(() => _currentRoute = route),
            themeConfig: themeConfig,
          ),

          // Main content
          Expanded(
            child: Column(
              children: [
                // AppBar
                Container(
                  height: 64,
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.surface,
                    border: Border(
                      bottom: BorderSide(color: Colors.grey.shade200),
                    ),
                  ),
                  child: Row(
                    children: [
                      Text(
                        _titleFor(_currentRoute),
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const Spacer(),
                      // اطلاعات tenant
                      Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            auth.tenant?.name ?? '-',
                            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                          ),
                          Text(
                            '${auth.user?.name ?? ''} • ${auth.user?.role?.displayName ?? ''}',
                            style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                      const SizedBox(width: 12),
                      CircleAvatar(
                        backgroundColor: Theme.of(context).colorScheme.primary,
                        child: Text(
                          (auth.user?.name ?? '?').substring(0, 1),
                          style: const TextStyle(color: Colors.white),
                        ),
                      ),
                      const SizedBox(width: 12),
                      IconButton(
                        icon: const Icon(Icons.logout),
                        tooltip: 'خروج',
                        onPressed: () => context.read<AuthState>().logout(),
                      ),
                    ],
                  ),
                ),

                // Page content
                Expanded(child: _buildPage()),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPage() {
    switch (_currentRoute) {
      case 'dashboard':
        return const DashboardScreen();
      case 'products':
        return const ProductsScreen();
      case 'inventory':
        return const InventoryScreen();
      case 'warehouse_map':
        return const LayoutsListScreen();
      case 'invoices':
        return const InvoicesScreen();
      case 'parties':
        return const PartiesScreen();
      case 'accounting':
        return const AccountingScreen();
      case 'reports':
        return const PlaceholderScreen(title: 'گزارش‌ها', icon: Icons.bar_chart_outlined);
      case 'settings':
        return const PlaceholderScreen(title: 'تنظیمات', icon: Icons.settings_outlined);
      default:
        return const DashboardScreen();
    }
  }

  String _titleFor(String route) {
    switch (route) {
      case 'dashboard': return 'داشبورد';
      case 'products': return 'کالاها';
      case 'inventory': return 'انبار';
      case 'warehouse_map': return 'طراح نقشه انبار';
      case 'invoices': return 'فاکتورها';
      case 'parties': return 'اشخاص';
      case 'accounting': return 'حسابداری';
      case 'reports': return 'گزارش‌ها';
      case 'settings': return 'تنظیمات';
      default: return '';
    }
  }
}
