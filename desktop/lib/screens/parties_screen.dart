import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';

class PartiesScreen extends StatefulWidget {
  const PartiesScreen({super.key});

  @override
  State<PartiesScreen> createState() => _PartiesScreenState();
}

class _PartiesScreenState extends State<PartiesScreen> {
  List<dynamic> _parties = [];
  bool _isLoading = true;
  String? _error;
  String _searchQuery = '';
  String _typeFilter = '';
  int _currentPage = 1;
  int _totalPages = 1;
  int _total = 0;

  @override
  void initState() {
    super.initState();
    _loadParties();
  }

  Future<void> _loadParties({int page = 1}) async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = context.read<ApiClient>();
      final response = await api.get(
        '/v1/parties',
        query: {
          'page': page,
          'per_page': 15,
          if (_searchQuery.isNotEmpty) 'search': _searchQuery,
          if (_typeFilter.isNotEmpty) 'type': _typeFilter,
        },
      );

      final data = response['data'] as List<dynamic>? ?? [];
      final meta = response['meta'] as Map<String, dynamic>? ?? {};

      setState(() {
        _parties = data;
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
                  'اشخاص',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
              ),
              // فیلتر نوع
              Container(
                width: 180,
                height: 44,
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: DropdownButton<String>(
                  value: _typeFilter.isEmpty ? null : _typeFilter,
                  hint: const Text('همه انواع'),
                  isExpanded: true,
                  underline: const SizedBox(),
                  items: const [
                    DropdownMenuItem(value: 'customer', child: Text('مشتری')),
                    DropdownMenuItem(value: 'supplier', child: Text('تأمین‌کننده')),
                  ],
                  onChanged: (v) {
                    _typeFilter = v ?? '';
                    _loadParties(page: 1);
                  },
                ),
              ),
              const SizedBox(width: 12),
              SizedBox(
                width: 280,
                child: TextField(
                  decoration: InputDecoration(
                    hintText: 'جستجو...',
                    prefixIcon: const Icon(Icons.search),
                    isDense: true,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  onSubmitted: (value) {
                    _searchQuery = value;
                    _loadParties(page: 1);
                  },
                ),
              ),
              const SizedBox(width: 12),
              IconButton.filled(
                onPressed: () => _loadParties(page: _currentPage),
                icon: const Icon(Icons.refresh),
              ),
            ],
          ),
        ),
        Expanded(
          child: _isLoading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                  ? _buildError()
                  : _parties.isEmpty
                      ? _buildEmpty()
                      : _buildTable(),
        ),
        if (!_isLoading && _error == null && _parties.isNotEmpty)
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
                  Expanded(flex: 2, child: _HeaderCell('کد')),
                  Expanded(flex: 4, child: _HeaderCell('نام')),
                  Expanded(flex: 2, child: _HeaderCell('نوع')),
                  Expanded(flex: 3, child: _HeaderCell('موبایل')),
                  Expanded(flex: 2, child: _HeaderCell('مانده')),
                ],
              ),
            ),
            ..._parties.map((p) => _buildRow(p)),
          ],
        ),
      ),
    );
  }

  Widget _buildRow(dynamic p) {
    final type = p['type'] ?? '';
    final typeLabel = p['type_label'] ?? '';
    final balance = (p['balance'] ?? 0).toDouble();

    Color typeColor;
    switch (type) {
      case 'customer':
        typeColor = Colors.blue;
        break;
      case 'supplier':
        typeColor = Colors.purple;
        break;
      case 'both':
        typeColor = Colors.teal;
        break;
      default:
        typeColor = Colors.grey;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
      ),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Text(p['code'] ?? '-', style: const TextStyle(fontFamily: 'monospace', fontSize: 13)),
          ),
          Expanded(
            flex: 4,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(p['name'] ?? '-', style: const TextStyle(fontWeight: FontWeight.w500)),
                if (p['company_name'] != null && p['company_name'] != '')
                  Text(p['company_name'], style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
              ],
            ),
          ),
          Expanded(
            flex: 2,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: typeColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                typeLabel,
                style: TextStyle(fontSize: 11, color: typeColor, fontWeight: FontWeight.w600),
                textAlign: TextAlign.center,
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(p['mobile'] ?? p['phone'] ?? '-'),
          ),
          Expanded(
            flex: 2,
            child: Text(
              _formatPrice(balance),
              style: TextStyle(
                color: balance > 0 ? Colors.red : Colors.green,
                fontWeight: FontWeight.w600,
              ),
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
          Icon(Icons.people_outline, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          Text('شخصی یافت نشد', style: Theme.of(context).textTheme.titleMedium),
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
          Text('خطا: $_error'),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: () => _loadParties(),
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
            onPressed: _currentPage > 1 ? () => _loadParties(page: _currentPage - 1) : null,
            icon: const Icon(Icons.chevron_right),
          ),
          const SizedBox(width: 8),
          Text('صفحه $_currentPage از $_totalPages  •  $_total شخص'),
          const SizedBox(width: 8),
          IconButton(
            onPressed: _currentPage < _totalPages ? () => _loadParties(page: _currentPage + 1) : null,
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
