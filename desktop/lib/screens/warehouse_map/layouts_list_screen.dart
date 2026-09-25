import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';
import 'layout_editor_screen.dart';

class LayoutsListScreen extends StatefulWidget {
  const LayoutsListScreen({super.key});

  @override
  State<LayoutsListScreen> createState() => _LayoutsListScreenState();
}

class _LayoutsListScreenState extends State<LayoutsListScreen> {
  List<dynamic> _layouts = [];
  List<dynamic> _warehouses = [];
  bool _isLoading = true;
  String? _error;
  int? _selectedWarehouseId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<ApiClient>();
      final whResp = await api.get('/v1/warehouses');
      _warehouses = whResp['data'] ?? [];
      if (_warehouses.isNotEmpty) {
        _selectedWarehouseId = _warehouses.first['id'];
      }

      if (_selectedWarehouseId != null) {
        final layoutResp = await api.get('/v1/warehouse-map/layouts',
            query: {'warehouse_id': _selectedWarehouseId});
        _layouts = layoutResp['data'] ?? [];
      }

      setState(() => _isLoading = false);
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _loadLayouts(int warehouseId) async {
    setState(() {
      _selectedWarehouseId = warehouseId;
      _isLoading = true;
    });
    try {
      final api = context.read<ApiClient>();
      final resp = await api.get('/v1/warehouse-map/layouts', query: {'warehouse_id': warehouseId});
      setState(() {
        _layouts = resp['data'] ?? [];
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
              Text('طراح نقشه انبار',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold)),
              const SizedBox(width: 24),
              if (_warehouses.isNotEmpty)
                Container(
                  width: 240,
                  height: 44,
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.grey.shade300),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: DropdownButton<int>(
                    value: _selectedWarehouseId,
                    isExpanded: true,
                    underline: const SizedBox(),
                    items: _warehouses
                        .map<DropdownMenuItem<int>>((w) => DropdownMenuItem(
                              value: w['id'],
                              child: Text(w['name']),
                            ))
                        .toList(),
                    onChanged: (v) {
                      if (v != null) _loadLayouts(v);
                    },
                  ),
                ),
              const Spacer(),
              FilledButton.icon(
                onPressed: _selectedWarehouseId == null
                    ? null
                    : () async {
                        final result = await Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => LayoutEditorScreen(warehouseId: _selectedWarehouseId!),
                          ),
                        );
                        if (result == true) _loadLayouts(_selectedWarehouseId!);
                      },
                icon: const Icon(Icons.add),
                label: const Text('نقشه جدید'),
              ),
              const SizedBox(width: 12),
              IconButton.filled(
                onPressed: () {
                  if (_selectedWarehouseId != null) _loadLayouts(_selectedWarehouseId!);
                },
                icon: const Icon(Icons.refresh),
              ),
            ],
          ),
        ),
        Expanded(
          child: _isLoading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                  ? Center(child: Text('خطا: $_error'))
                  : _layouts.isEmpty
                      ? _buildEmpty()
                      : _buildList(),
        ),
      ],
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.map_outlined, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          const Text('هیچ نقشه‌ای ساخته نشده', style: TextStyle(fontSize: 16)),
          const SizedBox(height: 8),
          Text('روی «نقشه جدید» کلیک کنید', style: TextStyle(color: Colors.grey.shade600)),
        ],
      ),
    );
  }

  Widget _buildList() {
    return ListView.separated(
      padding: const EdgeInsets.symmetric(horizontal: 24),
      itemCount: _layouts.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, i) {
        final layout = _layouts[i];
        final status = layout['status'] ?? 'draft';
        final statusColor = status == 'published'
            ? Colors.green
            : status == 'archived'
                ? Colors.grey
                : Colors.orange;

        return Card(
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: statusColor.withValues(alpha: 0.15),
              child: Icon(Icons.map, color: statusColor),
            ),
            title: Text('نسخه ${layout['version']}',
                style: const TextStyle(fontWeight: FontWeight.bold)),
            subtitle: Text(
              '${layout['warehouse']?['name'] ?? ''} • ${layout['locations_count'] ?? 0} موقعیت',
            ),
            trailing: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    layout['status_label'] ?? '',
                    style: TextStyle(fontSize: 12, color: statusColor, fontWeight: FontWeight.w600),
                  ),
                ),
                const SizedBox(width: 12),
                IconButton(
                  icon: const Icon(Icons.edit),
                  tooltip: 'ویرایش',
                  onPressed: status == 'published'
                      ? null
                      : () async {
                          final result = await Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => LayoutEditorScreen(
                                warehouseId: layout['warehouse_id'],
                                layoutId: layout['id'],
                              ),
                            ),
                          );
                          if (result == true) _loadLayouts(_selectedWarehouseId!);
                        },
                ),
                IconButton(
                  icon: const Icon(Icons.visibility),
                  tooltip: 'مشاهده',
                  onPressed: () {
                    // نمایش فقط‌خواندنی در Sprint بعد
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
