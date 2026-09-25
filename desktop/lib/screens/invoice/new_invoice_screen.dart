import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';

class NewInvoiceScreen extends StatefulWidget {
  const NewInvoiceScreen({super.key});

  @override
  State<NewInvoiceScreen> createState() => _NewInvoiceScreenState();
}

class _InvoiceItem {
  int? productId;
  String productName = '';
  double quantity = 1;
  double unitPrice = 0;
  double taxPercent = 9;
  final _qtyCtrl = TextEditingController(text: '1');
  final _priceCtrl = TextEditingController(text: '0');
  final _discountCtrl = TextEditingController(text: '0');

  double get subtotal => quantity * unitPrice;
  double get discount => subtotal * (double.tryParse(_discountCtrl.text) ?? 0) / 100;
  double get afterDiscount => subtotal - discount;
  double get tax => afterDiscount * taxPercent / 100;
  double get total => afterDiscount + tax;
}

class _NewInvoiceScreenState extends State<NewInvoiceScreen> {
  String _type = 'sale';
  int? _partyId;
  int? _warehouseId;
  final List<_InvoiceItem> _items = [_InvoiceItem()];

  List<dynamic> _parties = [];
  List<dynamic> _warehouses = [];
  List<dynamic> _products = [];

  bool _isLoading = true;
  bool _isSubmitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final api = context.read<ApiClient>();
      final results = await Future.wait([
        api.get('/v1/parties', query: {'per_page': 200}),
        api.get('/v1/warehouses', query: {'per_page': 50}),
        api.get('/v1/products', query: {'per_page': 200}),
      ]);

      setState(() {
        _parties = results[0]['data'] ?? [];
        _warehouses = results[1]['data'] ?? [];
        _products = results[2]['data'] ?? [];
        _warehouseId = _warehouses.isNotEmpty ? _warehouses.first['id'] : null;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _submit() async {
    if (_partyId == null) {
      _showError('لطفاً طرف حساب را انتخاب کنید');
      return;
    }
    if (_warehouseId == null) {
      _showError('لطفاً انبار را انتخاب کنید');
      return;
    }
    if (_items.any((i) => i.productId == null)) {
      _showError('لطفاً همه ردیف‌ها کالا داشته باشند');
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final api = context.read<ApiClient>();
      final response = await api.post('/v1/invoices', body: {
        'type': _type,
        'party_id': _partyId,
        'warehouse_id': _warehouseId,
        'items': _items
            .map((i) => {
                  'product_id': i.productId,
                  'quantity': i.quantity,
                  'unit_price': i.unitPrice,
                  'tax_percent': i.taxPercent,
                  'discount_percent': double.tryParse(i._discountCtrl.text) ?? 0,
                })
            .toList(),
      });

      if (response['success'] == true) {
        final invoiceId = response['data']['id'];
        if (!mounted) return;

        final confirm = await showDialog<bool>(
          context: context,
          builder: (ctx) => AlertDialog(
            title: const Text('فاکتور ثبت شد'),
            content: Text('شماره: ${response['data']['number']}\n\nآیا می‌خواهید فاکتور را تایید کنید؟ (باعث کاهش/افزایش موجودی و ثبت سند حسابداری می‌شود)'),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('بعداً')),
              FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('تایید')),
            ],
          ),
        );

        if (confirm == true) {
          await api.post('/v1/invoices/$invoiceId/confirm');
        }

        if (!mounted) return;
        Navigator.pop(context, true);
      }
    } catch (e) {
      _showError('خطا: $e');
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: Colors.red),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('فاکتور جدید'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_forward),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text('خطا: $_error'))
              : _buildForm(),
    );
  }

  Widget _buildForm() {
    return Column(
      children: [
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _buildHeader(),
                const SizedBox(height: 24),
                _buildItemsSection(),
                const SizedBox(height: 24),
                _buildTotals(),
              ],
            ),
          ),
        ),
        _buildActions(),
      ],
    );
  }

  Widget _buildHeader() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('اطلاعات فاکتور', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _buildField(
                    label: 'نوع فاکتور',
                    child: DropdownButtonFormField<String>(
                      initialValue: _type,
                      decoration: const InputDecoration(isDense: true, border: OutlineInputBorder()),
                      items: const [
                        DropdownMenuItem(value: 'sale', child: Text('فروش')),
                        DropdownMenuItem(value: 'purchase', child: Text('خرید')),
                      ],
                      onChanged: (v) => setState(() => _type = v ?? 'sale'),
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  flex: 2,
                  child: _buildField(
                    label: 'طرف حساب',
                    child: DropdownButtonFormField<int>(
                      initialValue: _partyId,
                      isExpanded: true,
                      decoration: const InputDecoration(isDense: true, border: OutlineInputBorder()),
                      hint: const Text('انتخاب کنید...'),
                      items: _parties
                          .map<DropdownMenuItem<int>>((p) => DropdownMenuItem(
                                value: p['id'],
                                child: Text('${p['name']}${p['company_name'] != null ? ' - ${p['company_name']}' : ''}'),
                              ))
                          .toList(),
                      onChanged: (v) => setState(() => _partyId = v),
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildField(
                    label: 'انبار',
                    child: DropdownButtonFormField<int>(
                      initialValue: _warehouseId,
                      decoration: const InputDecoration(isDense: true, border: OutlineInputBorder()),
                      items: _warehouses
                          .map<DropdownMenuItem<int>>((w) => DropdownMenuItem(value: w['id'], child: Text(w['name'])))
                          .toList(),
                      onChanged: (v) => setState(() => _warehouseId = v),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildField({required String label, required Widget child}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
        const SizedBox(height: 6),
        child,
      ],
    );
  }

  Widget _buildItemsSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Text('ردیف‌های فاکتور', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                const Spacer(),
                FilledButton.tonalIcon(
                  onPressed: () => setState(() => _items.add(_InvoiceItem())),
                  icon: const Icon(Icons.add, size: 18),
                  label: const Text('افزودن ردیف'),
                ),
              ],
            ),
            const SizedBox(height: 16),
            // هدر ستون‌ها
            Container(
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 8),
              decoration: BoxDecoration(
                color: Colors.grey.shade50,
                borderRadius: BorderRadius.circular(6),
              ),
              child: const Row(
                children: [
                  Expanded(flex: 4, child: _ColHeader('کالا')),
                  Expanded(flex: 2, child: _ColHeader('تعداد')),
                  Expanded(flex: 3, child: _ColHeader('قیمت واحد')),
                  Expanded(flex: 2, child: _ColHeader('تخفیف %')),
                  Expanded(flex: 2, child: _ColHeader('مالیات %')),
                  Expanded(flex: 3, child: _ColHeader('جمع')),
                  SizedBox(width: 40),
                ],
              ),
            ),
            const SizedBox(height: 8),
            ..._items.asMap().entries.map((e) => _buildItemRow(e.key, e.value)),
          ],
        ),
      ),
    );
  }

  Widget _buildItemRow(int index, _InvoiceItem item) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Expanded(
            flex: 4,
            child: DropdownButtonFormField<int>(
              initialValue: item.productId,
              isExpanded: true,
              decoration: const InputDecoration(isDense: true, border: OutlineInputBorder()),
              hint: const Text('انتخاب کالا'),
              items: _products
                  .map<DropdownMenuItem<int>>((p) => DropdownMenuItem(
                        value: p['id'],
                        child: Text('${p['code']} - ${p['name']}', overflow: TextOverflow.ellipsis),
                      ))
                  .toList(),
              onChanged: (v) {
                setState(() {
                  item.productId = v;
                  final prod = _products.firstWhere((p) => p['id'] == v);
                  item.productName = prod['name'];
                  final price = _type == 'sale'
                      ? (prod['pricing']?['sale_price'] ?? 0)
                      : (prod['pricing']?['purchase_price'] ?? 0);
                  item.unitPrice = (price as num).toDouble();
                  item._priceCtrl.text = item.unitPrice.toStringAsFixed(0);
                });
              },
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            flex: 2,
            child: TextField(
              controller: item._qtyCtrl,
              decoration: const InputDecoration(isDense: true, border: OutlineInputBorder()),
              keyboardType: TextInputType.number,
              onChanged: (v) => setState(() => item.quantity = double.tryParse(v) ?? 0),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            flex: 3,
            child: TextField(
              controller: item._priceCtrl,
              decoration: const InputDecoration(isDense: true, border: OutlineInputBorder()),
              keyboardType: TextInputType.number,
              onChanged: (v) => setState(() => item.unitPrice = double.tryParse(v) ?? 0),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            flex: 2,
            child: TextField(
              controller: item._discountCtrl,
              decoration: const InputDecoration(isDense: true, border: OutlineInputBorder()),
              keyboardType: TextInputType.number,
              onChanged: (v) => setState(() {}),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            flex: 2,
            child: TextFormField(
              initialValue: item.taxPercent.toStringAsFixed(0),
              decoration: const InputDecoration(isDense: true, border: OutlineInputBorder()),
              keyboardType: TextInputType.number,
              onChanged: (v) => setState(() => item.taxPercent = double.tryParse(v) ?? 0),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            flex: 3,
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
              decoration: BoxDecoration(
                color: Colors.green.shade50,
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                _formatNumber(item.total),
                style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.green),
                textAlign: TextAlign.left,
              ),
            ),
          ),
          SizedBox(
            width: 40,
            child: IconButton(
              icon: const Icon(Icons.delete_outline, color: Colors.red),
              onPressed: _items.length > 1
                  ? () => setState(() => _items.removeAt(index))
                  : null,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTotals() {
    double subtotal = 0;
    double discount = 0;
    double tax = 0;
    for (final i in _items) {
      subtotal += i.subtotal;
      discount += i.discount;
      tax += i.tax;
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            _totalRow('جمع کل:', subtotal),
            _totalRow('تخفیف:', discount),
            _totalRow('مالیات:', tax),
            const Divider(),
            _totalRow('مبلغ قابل پرداخت:', subtotal - discount + tax, bold: true, color: Colors.green),
          ],
        ),
      ),
    );
  }

  Widget _totalRow(String label, double value, {bool bold = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Text(label, style: TextStyle(fontWeight: bold ? FontWeight.bold : FontWeight.normal, fontSize: bold ? 16 : 14)),
          const Spacer(),
          Text(
            _formatNumber(value),
            style: TextStyle(
              fontWeight: bold ? FontWeight.bold : FontWeight.w500,
              fontSize: bold ? 16 : 14,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActions() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Colors.grey.shade200)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.end,
        children: [
          TextButton(
            onPressed: _isSubmitting ? null : () => Navigator.pop(context),
            child: const Text('انصراف'),
          ),
          const SizedBox(width: 12),
          FilledButton.icon(
            onPressed: _isSubmitting ? null : _submit,
            icon: _isSubmitting
                ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                : const Icon(Icons.save),
            label: Text(_isSubmitting ? 'در حال ثبت...' : 'ثبت فاکتور'),
          ),
        ],
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

class _ColHeader extends StatelessWidget {
  final String text;
  const _ColHeader(this.text);

  @override
  Widget build(BuildContext context) {
    return Text(text, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600));
  }
}
