import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';

class InventoryScreen extends StatefulWidget {
  const InventoryScreen({super.key});

  @override
  State<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen> {
  List<dynamic> _stocks = [];
  bool _isLoading = true;
  String? _error;
  String _searchQuery = '';
  int _currentPage = 1;
  int _totalPages = 1;
  int _total = 0;

  @override
  void initState() {
    super.initState();
    _loadStocks();
  }

  Future<void> _loadStocks({int page = 1}) async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = context.read<ApiClient>();
      final response = await api.get(
        '/v1/inventory/stock',
        query: {
          'page': page,
          'per_page': 15,
          'only_available': true,
          if (_searchQuery.isNotEmpty) 'search': _searchQuery,
        },
      );

      final data = response['data'] as List<dynamic>? ?? [];
      final meta = response['meta'] as Map<String, dynamic>? ?? {};

      setState(() {
        _stocks = data;
        _currentPage = meta['current_page'] ?? 1;
        _totalPages = meta['last_page'] ?? 1;
        _total = meta['total'] ?? 0;
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
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(24),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  'موجودی انبار',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
              ),
              SizedBox(
                width: 320,
                child: TextField(
                  decoration: InputDecoration(
                    hintText: 'جستجو در کد یا نام کالا...',
                    prefixIcon: const Icon(Icons.search),
                    isDense: true,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  onSubmitted: (value) {
                    _searchQuery = value;
                    _loadStocks(page: 1);
                  },
                ),
              ),
              const SizedBox(width: 12),
              IconButton.filled(
                onPressed: () => _loadStocks(page: _currentPage),
                icon: const Icon(Icons.refresh),
                tooltip: 'بروزرسانی',
              ),
            ],
          ),
        ),
        Expanded(
          child: _isLoading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                  ? _buildError()
                  : _stocks.isEmpty
                      ? _buildEmpty()
                      : _buildTable(),
        ),
        if (!_isLoading && _error == null && _stocks.isNotEmpty)
          _buildPagination(),
      ],
    );
  }

  Widget _buildTable() {
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: Card(
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: Colors.grey.shade50,
                border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
              ),
              child: const Row(
                children: [
                  Expanded(flex: 2, child: _HeaderCell('کد کالا')),
                  Expanded(flex: 4, child: _HeaderCell('نام کالا')),
                  Expanded(flex: 2, child: _HeaderCell('انبار')),
                  Expanded(flex: 2, child: _HeaderCell('موجودی')),
                  Expanded(flex: 2, child: _HeaderCell('میانگین')),
                  Expanded(flex: 2, child: _HeaderCell('ارزش')),
                ],
              ),
            ),
            ..._stocks.map((s) => _buildRow(s)),
          ],
        ),
      ),
    );
  }

  Widget _buildRow(dynamic s) {
    final product = s['product'] ?? {};
    final warehouse = s['warehouse'] ?? {};
    final isBelowReorder = s['is_below_reorder'] == true;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: isBelowReorder ? Colors.orange.shade50 : null,
        border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
      ),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Text(
              product['code'] ?? '-',
              style: const TextStyle(fontFamily: 'monospace', fontSize: 13),
            ),
          ),
          Expanded(
            flex: 4,
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    product['name'] ?? '-',
                    style: const TextStyle(fontWeight: FontWeight.w500),
                  ),
                ),
                if (isBelowReorder)
                  const Icon(Icons.warning_amber, color: Colors.orange, size: 18),
              ],
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(warehouse['name'] ?? '-'),
          ),
          Expanded(
            flex: 2,
            child: Text(
              '${(s['quantity'] ?? 0).toStringAsFixed(0)} ${product['unit'] ?? ''}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(_formatPrice((s['average_cost'] ?? 0).toDouble())),
          ),
          Expanded(
            flex: 2,
            child: Text(
              _formatPrice((s['stock_value'] ?? 0).toDouble()),
              style: const TextStyle(color: Colors.green, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.inventory_2_outlined, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          Text('موجودی یافت نشد', style: Theme.of(context).textTheme.titleMedium),
        ],
      ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.error_outline, size: 64, color: Colors.red.shade400),
          const SizedBox(height: 16),
          const Text('خطا در دریافت اطلاعات'),
          const SizedBox(height: 8),
          Text(_error!, style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: () => _loadStocks(),
            icon: const Icon(Icons.refresh),
            label: const Text('تلاش مجدد'),
          ),
        ],
      ),
    );
  }

  Widget _buildPagination() {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            onPressed: _currentPage > 1 ? () => _loadStocks(page: _currentPage - 1) : null,
            icon: const Icon(Icons.chevron_right),
          ),
          const SizedBox(width: 8),
          Text('صفحه $_currentPage از $_totalPages  •  $_total ردیف'),
          const SizedBox(width: 8),
          IconButton(
            onPressed: _currentPage < _totalPages ? () => _loadStocks(page: _currentPage + 1) : null,
            icon: const Icon(Icons.chevron_left),
          ),
        ],
      ),
    );
  }

  String _formatPrice(double n) {
    final s = n.toStringAsFixed(0);
    final buffer = StringBuffer();
    for (int i = 0; i < s.length; i++) {
      if (i > 0 && (s.length - i) % 3 == 0) buffer.write(',');
      buffer.write(s[i]);
    }
    return buffer.toString();
  }
}

class _HeaderCell extends StatelessWidget {
  final String text;
  const _HeaderCell(this.text);

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: const TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w600,
        color: Colors.black87,
      ),
    );
  }
}
