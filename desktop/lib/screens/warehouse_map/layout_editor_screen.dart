import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';
import '../../models/layout_item.dart';
import 'layout_painter.dart';

class LayoutEditorScreen extends StatefulWidget {
  final int warehouseId;
  final int? layoutId; // اگر موجود بود، ویرایش

  const LayoutEditorScreen({
    super.key,
    required this.warehouseId,
    this.layoutId,
  });

  @override
  State<LayoutEditorScreen> createState() => _LayoutEditorScreenState();
}

class _LayoutEditorScreenState extends State<LayoutEditorScreen> {
  final List<LayoutItem> _items = [];
  final List<List<LayoutItem>> _history = [];
  int _historyIndex = -1;

  String? _selectedId;
  double _pixelsPerMeter = 20;
  double _totalWidth = 50;
  double _totalHeight = 30;
  String _layoutStatus = 'draft';
  int? _currentLayoutId;
  bool _isLoading = true;
  bool _isSaving = false;
  String? _error;

  LayoutItemType _currentTool = LayoutItemType.rack;
  bool _showGrid = true;
  int _codeCounter = 1;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final api = context.read<ApiClient>();

      if (widget.layoutId != null) {
        // بارگذاری نقشه موجود
        final response = await api.get('/v1/warehouse-map/layouts/${widget.layoutId}');
        final data = response['data'];
        _currentLayoutId = data['id'];
        _layoutStatus = data['status'];
        _totalWidth = (data['total_width'] ?? 50).toDouble();
        _totalHeight = (data['total_height'] ?? 30).toDouble();

        final locations = data['locations'] as List<dynamic>? ?? [];
        _items.clear();
        for (final loc in locations) {
          _items.add(LayoutItem.fromApi(loc));
        }
        _codeCounter = _items.length + 1;
      } else {
        // ایجاد نقشه جدید
        final response = await api.post('/v1/warehouse-map/layouts', body: {
          'warehouse_id': widget.warehouseId,
          'total_width': _totalWidth,
          'total_height': _totalHeight,
        });
        _currentLayoutId = response['data']['id'];
        _layoutStatus = 'draft';
      }

      _pushHistory();
      setState(() => _isLoading = false);
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  void _pushHistory() {
    // حذف redoهای بعد از ایندکس فعلی
    if (_historyIndex < _history.length - 1) {
      _history.removeRange(_historyIndex + 1, _history.length);
    }
    _history.add(_items.map((e) => e.copy()).toList());
    _historyIndex = _history.length - 1;
  }

  void _undo() {
    if (_historyIndex > 0) {
      setState(() {
        _historyIndex--;
        _items.clear();
        _items.addAll(_history[_historyIndex].map((e) => e.copy()));
      });
    }
  }

  void _redo() {
    if (_historyIndex < _history.length - 1) {
      setState(() {
        _historyIndex++;
        _items.clear();
        _items.addAll(_history[_historyIndex].map((e) => e.copy()));
      });
    }
  }

  LayoutItem? get _selected {
    if (_selectedId == null) return null;
    try {
      return _items.firstWhere((e) => e.id == _selectedId);
    } catch (_) {
      return null;
    }
  }

  void _addItem(Offset canvasPos) {
    final x = (canvasPos.dx / _pixelsPerMeter).roundToDouble();
    final y = (canvasPos.dy / _pixelsPerMeter).roundToDouble();

    final code = switch (_currentTool) {
      LayoutItemType.zone => 'Z-${_codeCounter.toString().padLeft(2, '0')}',
      LayoutItemType.aisle => 'A-${_codeCounter.toString().padLeft(2, '0')}',
      LayoutItemType.rack => 'R-${_codeCounter.toString().padLeft(2, '0')}',
      LayoutItemType.shelf => 'S-${_codeCounter.toString().padLeft(2, '0')}',
      LayoutItemType.bin => 'B-${_codeCounter.toString().padLeft(2, '0')}',
    };

    final item = LayoutItem(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      code: code,
      name: '${_currentTool.label} $code',
      type: _currentTool,
      x: x,
      y: y,
      width: _currentTool == LayoutItemType.zone ? 10 : 2,
      depth: _currentTool == LayoutItemType.zone ? 6 : 1,
    );

    setState(() {
      _items.add(item);
      _selectedId = item.id;
      _codeCounter++;
      _pushHistory();
    });
  }

  Future<void> _save() async {
    if (_currentLayoutId == null) return;
    setState(() => _isSaving = true);

    try {
      final api = context.read<ApiClient>();

      // حذف همه locations موجود
      for (final item in _items) {
        try {
          await api.delete('/v1/warehouse-map/locations/${item.id}');
        } catch (_) {}
      }

      // ذخیره همه به صورت bulk
      final response = await api.post(
        '/v1/warehouse-map/layouts/$_currentLayoutId/locations/bulk',
        body: {
          'locations': _items.map((e) => e.toApi()).toList(),
        },
      );

      final created = response['data']['created'] as List<dynamic>? ?? [];

      // نگاشت IDs جدید
      setState(() {
        for (int i = 0; i < _items.length && i < created.length; i++) {
          _items[i] = LayoutItem.fromApi(created[i]);
        }
        _isSaving = false;
      });

      _showMessage('✅ نقشه با موفقیت ذخیره شد (${created.length} موقعیت)');
    } catch (e) {
      setState(() => _isSaving = false);
      _showMessage('❌ خطا در ذخیره: $e');
    }
  }

  Future<void> _publish() async {
    if (_currentLayoutId == null) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('انتشار نقشه'),
        content: const Text(
            'با انتشار، این نسخه به عنوان نقشه رسمی انبار تعیین می‌شود. نقشه‌های منتشر شده قابل ویرایش نیستند.\n\nآیا مطمئن هستید؟'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('لغو')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('انتشار')),
        ],
      ),
    );

    if (confirmed != true) return;
    if (!mounted) return;

    try {
      await context.read<ApiClient>().post(
            '/v1/warehouse-map/layouts/$_currentLayoutId/publish',
          );
      setState(() => _layoutStatus = 'published');
      _showMessage('✅ نقشه منتشر شد');
    } catch (e) {
      _showMessage('❌ خطا: $e');
    }
  }

  void _deleteSelected() {
    if (_selectedId == null) return;
    setState(() {
      _items.removeWhere((e) => e.id == _selectedId);
      _selectedId = null;
      _pushHistory();
    });
  }

  void _showMessage(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_error != null) {
      return Scaffold(
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('خطا: $_error'),
              const SizedBox(height: 16),
              FilledButton(onPressed: () => Navigator.pop(context), child: const Text('بازگشت')),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('طراح نقشه انبار - $_layoutStatus'),
        actions: [
          IconButton(
            icon: const Icon(Icons.undo),
            tooltip: 'بازگشت',
            onPressed: _historyIndex > 0 ? _undo : null,
          ),
          IconButton(
            icon: const Icon(Icons.redo),
            tooltip: 'جلو',
            onPressed: _historyIndex < _history.length - 1 ? _redo : null,
          ),
          const VerticalDivider(),
          IconButton(
            icon: Icon(_showGrid ? Icons.grid_on : Icons.grid_off),
            tooltip: 'نمایش شبکه',
            onPressed: () => setState(() => _showGrid = !_showGrid),
          ),
          const VerticalDivider(),
          FilledButton.tonalIcon(
            onPressed: _isSaving ? null : _save,
            icon: _isSaving
                ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                : const Icon(Icons.save, size: 18),
            label: const Text('ذخیره'),
          ),
          const SizedBox(width: 8),
          if (_layoutStatus == 'draft')
            FilledButton.icon(
              onPressed: _publish,
              icon: const Icon(Icons.publish, size: 18),
              label: const Text('انتشار'),
            ),
          const SizedBox(width: 16),
        ],
      ),
      body: Row(
        children: [
          _buildToolbar(),
          Expanded(child: _buildCanvas()),
          _buildPropertiesPanel(),
        ],
      ),
    );
  }

  // ============= Toolbar (ابزارها) =============
  Widget _buildToolbar() {
    return Container(
      width: 80,
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(left: BorderSide(color: Colors.grey.shade200)),
      ),
      child: Column(
        children: [
          const SizedBox(height: 12),
          _toolIcon(LayoutItemType.zone, Icons.crop_square),
          _toolIcon(LayoutItemType.aisle, Icons.horizontal_rule),
          _toolIcon(LayoutItemType.rack, Icons.view_module),
          _toolIcon(LayoutItemType.shelf, Icons.layers),
          _toolIcon(LayoutItemType.bin, Icons.inbox),
          const Divider(),
          IconButton(
            icon: const Icon(Icons.delete_outline, color: Colors.red),
            tooltip: 'حذف انتخاب‌شده',
            onPressed: _selectedId != null ? _deleteSelected : null,
          ),
          const Spacer(),
          IconButton(
            icon: const Icon(Icons.zoom_in),
            onPressed: () => setState(() => _pixelsPerMeter = (_pixelsPerMeter * 1.2).clamp(5, 60)),
          ),
          IconButton(
            icon: const Icon(Icons.zoom_out),
            onPressed: () => setState(() => _pixelsPerMeter = (_pixelsPerMeter / 1.2).clamp(5, 60)),
          ),
          Padding(
            padding: const EdgeInsets.all(8),
            child: Text(
              '${_pixelsPerMeter.toStringAsFixed(0)} px/m',
              style: const TextStyle(fontSize: 10),
            ),
          ),
        ],
      ),
    );
  }

  Widget _toolIcon(LayoutItemType type, IconData icon) {
    final isActive = _currentTool == type;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      child: Tooltip(
        message: type.label,
        child: InkWell(
          borderRadius: BorderRadius.circular(8),
          onTap: () => setState(() => _currentTool = type),
          child: Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: isActive ? type.color.withValues(alpha: 0.2) : Colors.grey.shade100,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(
                color: isActive ? type.color : Colors.transparent,
                width: 2,
              ),
            ),
            child: Icon(icon, color: isActive ? type.color : Colors.grey.shade600),
          ),
        ),
      ),
    );
  }

  // ============= Canvas =============
  Widget _buildCanvas() {
    final canvasWidth = _totalWidth * _pixelsPerMeter;
    final canvasHeight = _totalHeight * _pixelsPerMeter;

    return Container(
      color: Colors.grey.shade100,
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: SizedBox(
              width: canvasWidth,
              height: canvasHeight,
              child: GestureDetector(
                onTapUp: (details) {
                  // اگر روی آیتم کلیک نکردیم، آیتم جدید بساز
                  final item = _hitTest(details.localPosition);
                  if (item != null) {
                    setState(() => _selectedId = item.id);
                  } else {
                    _addItem(details.localPosition);
                  }
                },
                onPanStart: (details) {
                  final item = _hitTest(details.localPosition);
                  if (item != null) {
                    setState(() => _selectedId = item.id);
                  }
                },
                onPanUpdate: (details) {
                  final item = _selected;
                  if (item == null) return;
                  setState(() {
                    final dx = details.delta.dx / _pixelsPerMeter;
                    final dy = details.delta.dy / _pixelsPerMeter;
                    item.x = (item.x + dx).roundToDouble().clamp(0, _totalWidth - item.width);
                    item.y = (item.y + dy).roundToDouble().clamp(0, _totalHeight - item.depth);
                  });
                },
                onPanEnd: (_) => _pushHistory(),
                child: CustomPaint(
                  painter: LayoutPainter(
                    items: _items,
                    pixelsPerMeter: _pixelsPerMeter,
                    gridSize: 1,
                    selectedId: _selectedId,
                    showGrid: _showGrid,
                  ),
                  size: Size(canvasWidth, canvasHeight),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  LayoutItem? _hitTest(Offset pos) {
    final x = pos.dx / _pixelsPerMeter;
    final y = pos.dy / _pixelsPerMeter;

    for (int i = _items.length - 1; i >= 0; i--) {
      final item = _items[i];
      if (x >= item.x &&
          x <= item.x + item.width &&
          y >= item.y &&
          y <= item.y + item.depth) {
        return item;
      }
    }
    return null;
  }

  // ============= Properties Panel =============
  Widget _buildPropertiesPanel() {
    final item = _selected;

    return Container(
      width: 280,
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(right: BorderSide(color: Colors.grey.shade200)),
      ),
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: item == null
            ? _buildEmptyPanel()
            : _buildItemProperties(item),
      ),
    );
  }

  Widget _buildEmptyPanel() {
    return Column(
      children: [
        const SizedBox(height: 40),
        Icon(Icons.touch_app, size: 60, color: Colors.grey.shade300),
        const SizedBox(height: 16),
        const Text(
          'برای افزودن آیتم جدید، روی Canvas کلیک کنید',
          textAlign: TextAlign.center,
          style: TextStyle(color: Colors.grey),
        ),
        const SizedBox(height: 24),
        const Divider(),
        const SizedBox(height: 12),
        _stat('تعداد کل', _items.length.toString()),
        _stat('قفسه', _items.where((e) => e.type == LayoutItemType.rack).length.toString()),
        _stat('منطقه', _items.where((e) => e.type == LayoutItemType.zone).length.toString()),
      ],
    );
  }

  Widget _buildItemProperties(LayoutItem item) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Container(
              width: 12,
              height: 12,
              decoration: BoxDecoration(color: item.type.color, shape: BoxShape.circle),
            ),
            const SizedBox(width: 8),
            Text(item.type.label, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        const SizedBox(height: 16),

        _field('کد', item.code, (v) => setState(() => item.code = v)),
        _field('نام', item.name, (v) => setState(() => item.name = v)),

        const SizedBox(height: 16),
        const Divider(),
        const SizedBox(height: 8),
        const Text('موقعیت', style: TextStyle(fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),

        Row(children: [
          Expanded(child: _numField('X', item.x, (v) => setState(() => item.x = v))),
          const SizedBox(width: 8),
          Expanded(child: _numField('Y', item.y, (v) => setState(() => item.y = v))),
        ]),

        const SizedBox(height: 16),
        const Text('ابعاد', style: TextStyle(fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),

        Row(children: [
          Expanded(child: _numField('عرض', item.width, (v) => setState(() => item.width = v))),
          const SizedBox(width: 8),
          Expanded(child: _numField('عمق', item.depth, (v) => setState(() => item.depth = v))),
        ]),

        const SizedBox(height: 8),
        _numField('ارتفاع', item.height, (v) => setState(() => item.height = v)),

        const SizedBox(height: 16),
        _field(
          'ظرفیت',
          item.capacity?.toString() ?? '',
          (v) => setState(() => item.capacity = int.tryParse(v)),
        ),

        const SizedBox(height: 24),
        OutlinedButton.icon(
          onPressed: _deleteSelected,
          icon: const Icon(Icons.delete_outline, color: Colors.red),
          label: const Text('حذف', style: TextStyle(color: Colors.red)),
        ),
      ],
    );
  }

  Widget _field(String label, String value, ValueChanged<String> onChange) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        initialValue: value,
        decoration: InputDecoration(
          labelText: label,
          isDense: true,
          border: const OutlineInputBorder(),
        ),
        onChanged: onChange,
      ),
    );
  }

  Widget _numField(String label, double value, ValueChanged<double> onChange) {
    return TextFormField(
      initialValue: value.toString(),
      decoration: InputDecoration(
        labelText: label,
        isDense: true,
        border: const OutlineInputBorder(),
      ),
      keyboardType: TextInputType.number,
      onChanged: (v) => onChange(double.tryParse(v) ?? 0),
    );
  }

  Widget _stat(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Text(label, style: TextStyle(color: Colors.grey.shade600)),
          const Spacer(),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }
}
