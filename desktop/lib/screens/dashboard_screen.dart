import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';
import '../widgets/kpi_card.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic>? _summary;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = context.read<ApiClient>();
      final response = await api.get('/v1/inventory/summary');
      setState(() {
        _summary = response['data'] as Map<String, dynamic>?;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();

    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    final totalStock = (_summary?['total_quantity'] ?? 0).toDouble();
    final totalValue = (_summary?['total_value'] ?? 0).toDouble();
    final belowReorder = _summary?['below_reorder_count'] ?? 0;

    return RefreshIndicator(
      onRefresh: _loadData,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // خوش‌آمد
            Text(
              'خوش آمدید، ${auth.user?.name ?? ''}',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 4),
            Text(
              'خلاصه‌ای از وضعیت کسب‌وکار شما',
              style: TextStyle(color: Colors.grey.shade600),
            ),
            const SizedBox(height: 24),

            // KPI Cards
            LayoutBuilder(
              builder: (context, constraints) {
                final cardWidth = (constraints.maxWidth - 48) / 4;
                return Wrap(
                  spacing: 16,
                  runSpacing: 16,
                  children: [
                    SizedBox(
                      width: cardWidth.clamp(200, double.infinity),
                      child: KpiCard(
                        title: 'موجودی کل',
                        value: _formatNumber(totalStock),
                        icon: Icons.inventory_2_outlined,
                        color: Colors.blue,
                      ),
                    ),
                    SizedBox(
                      width: cardWidth.clamp(200, double.infinity),
                      child: KpiCard(
                        title: 'ارزش موجودی',
                        value: '${_formatNumber(totalValue)} ریال',
                        icon: Icons.attach_money,
                        color: Colors.green,
                      ),
                    ),
                    SizedBox(
                      width: cardWidth.clamp(200, double.infinity),
                      child: KpiCard(
                        title: 'هشدار موجودی',
                        value: belowReorder.toString(),
                        icon: Icons.warning_amber_outlined,
                        color: Colors.orange,
                        subtitle: belowReorder > 0 ? 'نیاز به سفارش' : 'همه چیز خوب',
                      ),
                    ),
                    SizedBox(
                      width: cardWidth.clamp(200, double.infinity),
                      child: KpiCard(
                        title: 'نقش کاربر',
                        value: auth.user?.role?.displayName ?? 'نامشخص',
                        icon: Icons.person_outline,
                        color: Colors.purple,
                      ),
                    ),
                  ],
                );
              },
            ),

            const SizedBox(height: 32),

            // بخش خالی برای نمودار
            Card(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.show_chart, color: Colors.blue),
                        const SizedBox(width: 8),
                        Text(
                          'روند فروش',
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      height: 200,
                      child: Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.bar_chart, size: 64, color: Colors.grey.shade300),
                            const SizedBox(height: 12),
                            Text(
                              'نمودار در Sprint بعدی',
                              style: TextStyle(color: Colors.grey.shade500),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            if (_error != null) ...[
              const SizedBox(height: 16),
              Card(
                color: Colors.red.shade50,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(Icons.error_outline, color: Colors.red.shade700),
                      const SizedBox(width: 12),
                      Expanded(child: Text('خطا: $_error')),
                      TextButton(onPressed: _loadData, child: const Text('تلاش مجدد')),
                    ],
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  String _formatNumber(double n) {
    final s = n.toStringAsFixed(0);
    final buffer = StringBuffer();
    for (int i = 0; i < s.length; i++) {
      if (i > 0 && (s.length - i) % 3 == 0) buffer.write(',');
      buffer.write(s[i]);
    }
    return buffer.toString();
  }
}
