import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';

class AccountingScreen extends StatefulWidget {
  const AccountingScreen({super.key});

  @override
  State<AccountingScreen> createState() => _AccountingScreenState();
}

class _AccountingScreenState extends State<AccountingScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabCtrl;

  @override
  void initState() {
    super.initState();
    _tabCtrl = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    _tabCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
          ),
          child: TabBar(
            controller: _tabCtrl,
            tabs: const [
              Tab(text: 'اسناد حسابداری', icon: Icon(Icons.book_outlined, size: 18)),
              Tab(text: 'کدینگ حساب‌ها', icon: Icon(Icons.account_tree_outlined, size: 18)),
              Tab(text: 'گزارش‌ها', icon: Icon(Icons.bar_chart, size: 18)),
            ],
          ),
        ),
        Expanded(
          child: TabBarView(
            controller: _tabCtrl,
            children: const [
              _JournalEntriesTab(),
              _AccountsTab(),
              _ReportsTab(),
            ],
          ),
        ),
      ],
    );
  }
}

// ============= Tab 1: Journal Entries =============
class _JournalEntriesTab extends StatefulWidget {
  const _JournalEntriesTab();

  @override
  State<_JournalEntriesTab> createState() => _JournalEntriesTabState();
}

class _JournalEntriesTabState extends State<_JournalEntriesTab> {
  List<dynamic> _entries = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<ApiClient>();
      final response = await api.get('/v1/accounting/journal-entries', query: {'per_page': 50});
      setState(() {
        _entries = response['data'] ?? [];
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
    if (_isLoading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Text('خطا: $_error'));
    if (_entries.isEmpty) return const Center(child: Text('سندی یافت نشد'));

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(24),
        itemCount: _entries.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (context, i) {
          final e = _entries[i];
          final status = e['status'] ?? 'draft';
          final statusColor = status == 'approved'
              ? Colors.green
              : status == 'cancelled'
                  ? Colors.red
                  : Colors.orange;

          return Card(
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: statusColor.withValues(alpha: 0.15),
                child: Icon(Icons.receipt, color: statusColor, size: 20),
              ),
              title: Text('${e['number']} - ${e['description']}',
                  style: const TextStyle(fontWeight: FontWeight.w600)),
              subtitle: Row(
                children: [
                  Text(e['entry_date'] ?? '-'),
                  const SizedBox(width: 16),
                  Text('بدهکار: ${_formatNum(e['total_debit'])}'),
                  const SizedBox(width: 8),
                  Text('بستانکار: ${_formatNum(e['total_credit'])}'),
                ],
              ),
              trailing: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  e['status_label'] ?? '',
                  style: TextStyle(fontSize: 12, color: statusColor, fontWeight: FontWeight.w600),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

// ============= Tab 2: Accounts Tree =============
class _AccountsTab extends StatefulWidget {
  const _AccountsTab();

  @override
  State<_AccountsTab> createState() => _AccountsTabState();
}

class _AccountsTabState extends State<_AccountsTab> {
  List<dynamic> _accounts = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<ApiClient>();
      final response = await api.get('/v1/accounting/accounts', query: {'tree': 'true'});
      setState(() {
        _accounts = response['data'] ?? [];
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
    if (_isLoading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Text('خطا: $_error'));

    return ListView.builder(
      padding: const EdgeInsets.all(24),
      itemCount: _accounts.length,
      itemBuilder: (context, i) => _buildTreeNode(_accounts[i], 0),
    );
  }

  Widget _buildTreeNode(dynamic account, int level) {
    final children = account['children'] as List<dynamic>? ?? [];
    final type = account['type'] ?? '';
    final typeColor = {
      'asset': Colors.blue,
      'liability': Colors.red,
      'equity': Colors.purple,
      'revenue': Colors.green,
      'expense': Colors.orange,
    }[type] ?? Colors.grey;

    return Padding(
      padding: EdgeInsets.only(right: level * 24.0, bottom: 4),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              color: level == 0 ? typeColor.withValues(alpha: 0.08) : Colors.grey.shade50,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                Container(
                  width: 4,
                  height: 20,
                  decoration: BoxDecoration(
                    color: typeColor,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                const SizedBox(width: 12),
                SizedBox(
                  width: 100,
                  child: Text(
                    account['code'] ?? '',
                    style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.w600),
                  ),
                ),
                Expanded(
                  child: Text(
                    account['name'] ?? '',
                    style: TextStyle(
                      fontWeight: level == 0 ? FontWeight.bold : FontWeight.normal,
                    ),
                  ),
                ),
                if (level == 0)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: typeColor.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      account['type_label'] ?? '',
                      style: TextStyle(fontSize: 11, color: typeColor, fontWeight: FontWeight.w600),
                    ),
                  ),
              ],
            ),
          ),
          ...children.map((c) => _buildTreeNode(c, level + 1)),
        ],
      ),
    );
  }
}

// ============= Tab 3: Reports =============
class _ReportsTab extends StatefulWidget {
  const _ReportsTab();

  @override
  State<_ReportsTab> createState() => _ReportsTabState();
}

class _ReportsTabState extends State<_ReportsTab> {
  String _reportType = 'trial_balance';
  Map<String, dynamic>? _data;
  bool _isLoading = false;
  String? _error;

  Future<void> _load() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = context.read<ApiClient>();
      final endpoint = {
        'trial_balance': '/v1/reports/trial-balance',
        'balance_sheet': '/v1/reports/balance-sheet',
        'income_statement': '/v1/reports/income-statement',
      }[_reportType]!;

      final response = await api.get(endpoint);
      setState(() {
        _data = response['data'];
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
                child: SegmentedButton<String>(
                  segments: const [
                    ButtonSegment(value: 'trial_balance', label: Text('تراز آزمایشی')),
                    ButtonSegment(value: 'balance_sheet', label: Text('ترازنامه')),
                    ButtonSegment(value: 'income_statement', label: Text('سود و زیان')),
                  ],
                  selected: {_reportType},
                  onSelectionChanged: (s) {
                    setState(() => _reportType = s.first);
                    _load();
                  },
                ),
              ),
              const SizedBox(width: 16),
              FilledButton.icon(
                onPressed: _load,
                icon: const Icon(Icons.refresh),
                label: const Text('نمایش'),
              ),
            ],
          ),
        ),
        Expanded(
          child: _isLoading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                  ? Center(child: Text('خطا: $_error'))
                  : _data == null
                      ? const Center(child: Text('گزارشی برای نمایش نیست. روی «نمایش» بزنید.'))
                      : _buildReport(),
        ),
      ],
    );
  }

  Widget _buildReport() {
    if (_reportType == 'trial_balance') {
      return _buildTrialBalance();
    } else if (_reportType == 'balance_sheet') {
      return _buildBalanceSheet();
    }
    return _buildIncomeStatement();
  }

  Widget _buildTrialBalance() {
    final accounts = (_data?['accounts'] ?? []) as List<dynamic>;
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Card(
        child: Column(
          children: [
            _buildTableHeader(['کد', 'نام', 'بدهکار', 'بستانکار', 'مانده']),
            ...accounts.map((a) => _buildTableRow([
                  a['code'] ?? '',
                  a['name'] ?? '',
                  _formatNum(a['debit']),
                  _formatNum(a['credit']),
                  _formatNum(a['balance']),
                ])),
            Divider(height: 1, color: Colors.grey.shade300),
            _buildTotalRow([
              'جمع کل',
              '',
              _formatNum(_data?['total_debit']),
              _formatNum(_data?['total_credit']),
              '',
            ]),
          ],
        ),
      ),
    );
  }

  Widget _buildBalanceSheet() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(child: _buildBSSection('دارایی‌ها', _data?['assets'] ?? [], _data?['total_assets'], Colors.blue)),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              children: [
                _buildBSSection('بدهی‌ها', _data?['liabilities'] ?? [], _data?['total_liabilities'], Colors.red),
                const SizedBox(height: 16),
                _buildBSSection('حقوق صاحبان سهام', _data?['equity'] ?? [], _data?['total_equity'], Colors.purple),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBSSection(String title, List<dynamic> items, dynamic total, Color color) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Row(
                children: [
                  Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: color)),
                  const Spacer(),
                  Text(_formatNum(total), style: TextStyle(fontWeight: FontWeight.bold, color: color)),
                ],
              ),
            ),
            const SizedBox(height: 12),
            if (items.isEmpty)
              const Text('موردی نیست', style: TextStyle(color: Colors.grey))
            else
              ...items.map((i) => Padding(
                    padding: const EdgeInsets.symmetric(vertical: 6),
                    child: Row(
                      children: [
                        SizedBox(width: 60, child: Text(i['code'] ?? '', style: const TextStyle(fontSize: 12))),
                        Expanded(child: Text(i['name'] ?? '')),
                        Text(_formatNum(i['amount'])),
                      ],
                    ),
                  )),
          ],
        ),
      ),
    );
  }

  Widget _buildIncomeStatement() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          _buildISSection('درآمدها', _data?['revenues'] ?? [], _data?['total_revenue'], Colors.green),
          const SizedBox(height: 16),
          _buildISSection('هزینه‌ها', _data?['expenses'] ?? [], _data?['total_expense'], Colors.orange),
          const SizedBox(height: 16),
          Card(
            color: Colors.blue.shade50,
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Row(
                children: [
                  const Text('سود (زیان) خالص:', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const Spacer(),
                  Text(
                    _formatNum(_data?['net_profit']),
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: (_data?['net_profit'] ?? 0) >= 0 ? Colors.green : Colors.red,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildISSection(String title, List<dynamic> items, dynamic total, Color color) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: color, fontSize: 16)),
                const Spacer(),
                Text(_formatNum(total), style: TextStyle(fontWeight: FontWeight.bold, color: color)),
              ],
            ),
            const Divider(),
            if (items.isEmpty)
              const Text('موردی نیست', style: TextStyle(color: Colors.grey))
            else
              ...items.map((i) => Padding(
                    padding: const EdgeInsets.symmetric(vertical: 6),
                    child: Row(
                      children: [
                        SizedBox(width: 60, child: Text(i['code'] ?? '', style: const TextStyle(fontSize: 12))),
                        Expanded(child: Text(i['name'] ?? '')),
                        Text(_formatNum(i['amount'])),
                      ],
                    ),
                  )),
          ],
        ),
      ),
    );
  }

  Widget _buildTableHeader(List<String> labels) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
      ),
      child: Row(
        children: labels
            .map((l) => Expanded(
                  child: Text(l,
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                ))
            .toList(),
      ),
    );
  }

  Widget _buildTableRow(List<String> values) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
      ),
      child: Row(
        children: values
            .map((v) => Expanded(
                  child: Text(v, style: const TextStyle(fontSize: 13)),
                ))
            .toList(),
      ),
    );
  }

  Widget _buildTotalRow(List<String> values) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      color: Colors.grey.shade50,
      child: Row(
        children: values
            .map((v) => Expanded(
                  child: Text(v, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                ))
            .toList(),
      ),
    );
  }
}

String _formatNum(dynamic n) {
  final v = (n ?? 0).toDouble();
  final s = v.toStringAsFixed(0);
  final buffer = StringBuffer();
  for (int i = 0; i < s.length; i++) {
    if (i > 0 && (s.length - i) % 3 == 0) buffer.write(',');
    buffer.write(s[i]);
  }
  return buffer.toString();
}
