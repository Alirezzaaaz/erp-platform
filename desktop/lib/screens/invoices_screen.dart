import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';

class InvoicesScreen extends StatefulWidget {
  const InvoicesScreen({super.key});

  @override
  State<InvoicesScreen> createState() => _InvoicesScreenState();
}

class _InvoicesScreenState extends State<InvoicesScreen> {
  List<dynamic> _invoices = [];
  bool _isLoading = true;
  String? _error;
  String _typeFilter = '';
  int _currentPage = 1;
  int _totalPages = 1;
  int _total = 0;

  @override
  void initState() {
    super.initState();
    _loadInvoices();
  }

  Future<void> _loadInvoices({int page = 1}) async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = context.read<ApiClient>();
      final response = await api.get(
        '/v1/invoices',
        query: {
          'page': page,
          'per_page': 15,
          if (_typeFilter.isNotEmpty) 'type': _typeFilter,
        },
      );

      final data = response['data'] as List<dynamic>? ?? [];
      final meta = response['meta'] as Map<String, dynamic>? ?? {};

      setState(() {
        _invoices = data;
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
                  'فاکتورها',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
              ),
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
                    DropdownMenuItem(value: 'sale', child: Text('فروش')),
                    DropdownMenuItem(value: 'purchase', child: Text('خرید')),
                    DropdownMenuItem(value: 'sale_return', child: Text('برگشت از فروش')),
                    DropdownMenuItem(value: 'purchase_return', child: Text('برگشت به تأمین‌کننده')),
                  ],
                  onChanged: (v) {
                    _typeFilter = v ?? '';
                    _loadInvoices(page: 1);
                  },
                ),
              ),
              const SizedBox(width: 12),
              IconButton.filled(
                onPressed: () => _loadInvoices(page: _currentPage),
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
                  : _invoices.isEmpty
                      ? _buildEmpty()
                      : _buildTable(),
        ),
        if (!_isLoading && _error == null && _invoices.isNotEmpty)
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
                  Expanded(flex: 3, child: _HeaderCell('شماره')),
                  Expanded(flex: 2, child: _HeaderCell('نوع')),
                  Expanded(flex: 4, child: _HeaderCell('طرف حساب')),
                  Expanded(flex: 2, child: _HeaderCell('تاریخ')),
                  Expanded(flex: 3, child: _HeaderCell('مبلغ کل')),
                  Expanded(flex: 2, child: _HeaderCell('وضعیت')),
                ],
              ),
            ),
            ..._invoices.map((i) => _buildRow(i)),
          ],
        ),
      ),
    );
  }

  Widget _buildRow(dynamic inv) {
    final party = inv['party'] ?? {};
    final status = inv['status'] ?? '';
    final type = inv['type'] ?? '';

    Color statusColor;
    switch (status) {
      case 'confirmed':
        statusColor = Colors.green;
        break;
      case 'cancelled':
        statusColor = Colors.red;
        break;
      default:
        statusColor = Colors.orange;
    }

    Color typeColor = type.contains('sale') && !type.contains('return')
        ? Colors.blue
        : type.contains('purchase') && !type.contains('return')
            ? Colors.purple
            : Colors.grey;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
      ),
      child: Row(
        children: [
          Expanded(
            flex: 3,
            child: Text(
              inv['number'] ?? '-',
              style: const TextStyle(fontFamily: 'monospace', fontSize: 13, fontWeight: FontWeight.w600),
            ),
          ),
          Expanded(
            flex: 2,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
              decoration: BoxDecoration(
                color: typeColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                inv['type_label'] ?? '',
                style: TextStyle(fontSize: 11, color: typeColor, fontWeight: FontWeight.w600),
                textAlign: TextAlign.center,
              ),
            ),
          ),
          Expanded(
            flex: 4,
            child: Text(party['name'] ?? '-', style: const TextStyle(fontWeight: FontWeight.w500)),
          ),
          Expanded(
            flex: 2,
            child: Text(
              inv['issue_date'] ?? '-',
              style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(
              _formatPrice((inv['total_amount'] ?? 0).toDouble()),
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
          Expanded(
            flex: 2,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: statusColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                inv['status_label'] ?? '',
                style: TextStyle(fontSize: 11, color: statusColor, fontWeight: FontWeight.w600),
                textAlign: TextAlign.center,
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
          Icon(Icons.receipt_long_outlined, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          Text('فاکتوری یافت نشد', style: Theme.of(context).textTheme.titleMedium),
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
            onPressed: () => _loadInvoices(),
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
            onPressed: _currentPage > 1 ? () => _loadInvoices(page: _currentPage - 1) : null,
            icon: const Icon(Icons.chevron_right),
          ),
          const SizedBox(width: 8),
          Text('صفحه $_currentPage از $_totalPages  •  $_total فاکتور'),
          const SizedBox(width: 8),
          IconButton(
            onPressed: _currentPage < _totalPages ? () => _loadInvoices(page: _currentPage + 1) : null,
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
