import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';

class ProductsScreen extends StatefulWidget {
  const ProductsScreen({super.key});

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  List<dynamic> _products = [];
  bool _isLoading = true;
  String? _error;
  String _searchQuery = '';
  int _currentPage = 1;
  int _totalPages = 1;
  int _total = 0;

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  Future<void> _loadProducts({int page = 1}) async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = context.read<ApiClient>();
      final response = await api.get(
        '/v1/products',
        query: {
          'page': page,
          'per_page': 15,
          if (_searchQuery.isNotEmpty) 'search': _searchQuery,
        },
      );

      final data = response['data'] as List<dynamic>? ?? [];
      final meta = response['meta'] as Map<String, dynamic>? ?? {};

      setState(() {
        _products = data;
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
        // هدر با جستجو
        Container(
          padding: const EdgeInsets.all(24),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  'کالاها',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
              ),
              SizedBox(
                width: 320,
                child: TextField(
                  decoration: InputDecoration(
                    hintText: 'جستجو در کد، نام یا بارکد...',
                    prefixIcon: const Icon(Icons.search),
                    isDense: true,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  onSubmitted: (value) {
                    _searchQuery = value;
                    _loadProducts(page: 1);
                  },
                ),
              ),
              const SizedBox(width: 12),
              IconButton.filled(
                onPressed: () => _loadProducts(page: _currentPage),
                icon: const Icon(Icons.refresh),
                tooltip: 'بروزرسانی',
              ),
            ],
          ),
        ),

        // جدول
        Expanded(
          child: _isLoading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                  ? _buildError()
                  : _products.isEmpty
                      ? _buildEmpty()
                      : _buildTable(),
        ),

        // صفحه‌بندی
        if (!_isLoading && _error == null && _products.isNotEmpty)
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
              child: Row(
                children: [
                  Expanded(flex: 2, child: const _HeaderCell('کد')),
                  Expanded(flex: 4, child: const _HeaderCell('نام کالا')),
                  Expanded(flex: 2, child: const _HeaderCell('بارکد')),
                  Expanded(flex: 2, child: const _HeaderCell('قیمت فروش')),
                  Expanded(flex: 1, child: const _HeaderCell('واحد')),
                ],
              ),
            ),
            ..._products.map((p) => _buildRow(p)),
          ],
        ),
      ),
    );
  }

  Widget _buildRow(dynamic p) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
      ),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Text(
              p['code'] ?? '-',
              style: const TextStyle(fontFamily: 'monospace', fontSize: 13),
            ),
          ),
          Expanded(
            flex: 4,
            child: Text(
              p['name'] ?? '-',
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(
              p['barcode'] ?? '-',
              style: TextStyle(fontFamily: 'monospace', fontSize: 12, color: Colors.grey.shade600),
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(
              _formatPrice((p['pricing']?['sale_price'] ?? 0).toDouble()),
              style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.green),
            ),
          ),
          Expanded(
            flex: 1,
            child: Text(
              p['unit']?['symbol'] ?? '-',
              style: TextStyle(color: Colors.grey.shade700),
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
          Text('کالایی یافت نشد', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          Text(
            _searchQuery.isNotEmpty ? 'جستجو نتیجه‌ای نداشت' : 'هنوز کالایی اضافه نشده',
            style: TextStyle(color: Colors.grey.shade500),
          ),
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
          Text('خطا در دریافت اطلاعات'),
          const SizedBox(height: 8),
          Text(_error!, style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: () => _loadProducts(),
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
            onPressed: _currentPage > 1 ? () => _loadProducts(page: _currentPage - 1) : null,
            icon: const Icon(Icons.chevron_right),
          ),
          const SizedBox(width: 8),
          Text(
            'صفحه $_currentPage از $_totalPages  •  $_total کالا',
            style: TextStyle(color: Colors.grey.shade700),
          ),
          const SizedBox(width: 8),
          IconButton(
            onPressed: _currentPage < _totalPages ? () => _loadProducts(page: _currentPage + 1) : null,
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
