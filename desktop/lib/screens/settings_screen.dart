import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen>
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
              Tab(text: 'اطلاعات کسب‌وکار', icon: Icon(Icons.business, size: 18)),
              Tab(text: 'کاربران و نقش‌ها', icon: Icon(Icons.people, size: 18)),
              Tab(text: 'شخصی‌سازی ظاهر', icon: Icon(Icons.palette, size: 18)),
            ],
          ),
        ),
        Expanded(
          child: TabBarView(
            controller: _tabCtrl,
            children: const [
              _TenantInfoTab(),
              _UsersRolesTab(),
              _ThemeTab(),
            ],
          ),
        ),
      ],
    );
  }
}

// ============= Tab 1: Tenant Info =============
class _TenantInfoTab extends StatelessWidget {
  const _TenantInfoTab();

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final tenant = auth.tenant;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle('اطلاعات کسب‌وکار'),
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  _infoRow('نام کسب‌وکار', tenant?.name ?? '-'),
                  _infoRow('زیردامنه', tenant?.subdomain ?? '-'),
                  _infoRow('نوع کسب‌وکار', _translateBusinessType(tenant?.businessType)),
                  _infoRow('اندازه', _translateSize(tenant?.sizeCategory)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),
          _sectionTitle('اطلاعات کاربر جاری'),
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  _infoRow('نام', auth.user?.name ?? '-'),
                  _infoRow('ایمیل', auth.user?.email ?? '-'),
                  _infoRow('موبایل', auth.user?.mobile ?? '-'),
                  _infoRow('نقش', auth.user?.role?.displayName ?? '-'),
                  _infoRow('آخرین ورود',
                      auth.user?.lastLoginAt?.toString().substring(0, 16) ?? '-'),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionTitle(String title) {
    return Text(title,
        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold));
  }

  Widget _infoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        children: [
          SizedBox(
            width: 160,
            child: Text(label, style: TextStyle(color: Colors.grey.shade600)),
          ),
          Expanded(
            child: Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
          ),
        ],
      ),
    );
  }

  String _translateBusinessType(String? type) {
    switch (type) {
      case 'retail': return 'خرده‌فروشی';
      case 'factory': return 'کارخانه';
      case 'wholesale': return 'عمده‌فروشی';
      case 'service': return 'خدمات';
      default: return type ?? '-';
    }
  }

  String _translateSize(String? size) {
    switch (size) {
      case 'small': return 'کوچک';
      case 'medium': return 'متوسط';
      case 'enterprise': return 'سازمانی';
      default: return size ?? '-';
    }
  }
}

// ============= Tab 2: Users & Roles =============
class _UsersRolesTab extends StatelessWidget {
  const _UsersRolesTab();

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final role = auth.user?.role;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text('نقش‌ها و دسترسی‌ها',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const Spacer(),
              FilledButton.tonalIcon(
                onPressed: () {},
                icon: const Icon(Icons.person_add, size: 18),
                label: const Text('کاربر جدید'),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.blue.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          role?.displayName ?? '-',
                          style: const TextStyle(
                              color: Colors.blue, fontWeight: FontWeight.bold),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Text('نقش شما: ${role?.name ?? '-'}',
                          style: TextStyle(color: Colors.grey.shade600)),
                    ],
                  ),
                  const SizedBox(height: 20),
                  const Text('دسترسی‌های فعال:',
                      style: TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: (role?.permissions ?? [])
                        .map<Widget>((p) => Chip(
                              label: Text(
                                p == '*' ? 'دسترسی کامل' : p,
                                style: const TextStyle(fontSize: 12),
                              ),
                              backgroundColor: p == '*'
                                  ? Colors.green.shade50
                                  : Colors.grey.shade100,
                            ))
                        .toList(),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ============= Tab 3: Theme Customization =============
class _ThemeTab extends StatefulWidget {
  const _ThemeTab();

  @override
  State<_ThemeTab> createState() => _ThemeTabState();
}

class _ThemeTabState extends State<_ThemeTab> {
  String _primaryColor = '#1976D2';
  String _secondaryColor = '#424242';
  String _fontFamily = 'Vazirmatn';
  int _borderRadius = 8;
  String _themeMode = 'light';
  bool _isSaving = false;

  final List<Map<String, String>> _colorPresets = [
    {'name': 'آبی پیش‌فرض', 'color': '#1976D2'},
    {'name': 'سبز زمرد', 'color': '#10B981'},
    {'name': 'بنفش سلطنتی', 'color': '#8B5CF6'},
    {'name': 'نارنجی گرم', 'color': '#F97316'},
    {'name': 'قرمز یاقوت', 'color': '#DC2626'},
    {'name': 'صورتی مرجانی', 'color': '#EC4899'},
    {'name': 'فیروزه‌ای', 'color': '#06B6D4'},
    {'name': 'نیلی', 'color': '#4F46E5'},
  ];

  final List<Map<String, String>> _fontPresets = [
    {'name': 'وزیرمتن', 'value': 'Vazirmatn'},
    {'name': 'ایران‌سنس', 'value': 'IRANSans'},
    {'name': 'شبنم', 'value': 'Shabnam'},
    {'name': 'ساحل', 'value': 'Sahel'},
  ];

  @override
  void initState() {
    super.initState();
    _loadCurrent();
  }

  void _loadCurrent() {
    final auth = context.read<AuthState>();
    final theme = auth.theme;
    if (theme != null) {
      _primaryColor = theme.theme.primaryColor;
      _secondaryColor = theme.theme.secondaryColor;
      _fontFamily = theme.theme.fontFamily;
      _borderRadius = theme.theme.borderRadius;
      _themeMode = theme.theme.themeMode;
    }
  }

  Future<void> _save() async {
    setState(() => _isSaving = true);

    // گرفتن reference قبل از await
    final api = context.read<ApiClient>();
    final auth = context.read<AuthState>();
    final messenger = ScaffoldMessenger.of(context);

    try {
      await api.put('/v1/theme', body: {
        'primaryColor': _primaryColor,
        'secondaryColor': _secondaryColor,
        'fontFamily': _fontFamily,
        'borderRadius': _borderRadius,
        'themeMode': _themeMode,
      });

      await auth.tryRestoreSession();

      messenger.showSnackBar(
        const SnackBar(
          content: Text('✅ تم با موفقیت ذخیره شد'),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      messenger.showSnackBar(
        SnackBar(content: Text('❌ خطا: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text('شخصی‌سازی ظاهر',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const Spacer(),
              FilledButton.icon(
                onPressed: _isSaving ? null : _save,
                icon: _isSaving
                    ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Icon(Icons.save, size: 18),
                label: const Text('ذخیره'),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // رنگ اصلی
          _sectionCard(
            title: 'رنگ اصلی',
            child: Wrap(
              spacing: 12,
              runSpacing: 12,
              children: _colorPresets.map((preset) {
                final isSelected = preset['color'] == _primaryColor;
                return InkWell(
                  onTap: () => setState(() => _primaryColor = preset['color']!),
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    width: 100,
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      border: Border.all(
                        color: isSelected ? Colors.black : Colors.grey.shade300,
                        width: isSelected ? 2 : 1,
                      ),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      children: [
                        Container(
                          height: 40,
                          decoration: BoxDecoration(
                            color: _hexToColor(preset['color']!),
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(preset['name']!, style: const TextStyle(fontSize: 11)),
                      ],
                    ),
                  ),
                );
              }).toList(),
            ),
          ),

          const SizedBox(height: 20),

          // رنگ ثانویه
          _sectionCard(
            title: 'رنگ ثانویه',
            child: Row(
              children: [
                Container(
                  width: 60,
                  height: 40,
                  decoration: BoxDecoration(
                    color: _hexToColor(_secondaryColor),
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                const SizedBox(width: 16),
                const Text('رنگ پیش‌فرض دکمه‌های ثانویه'),
              ],
            ),
          ),

          const SizedBox(height: 20),

          // فونت
          _sectionCard(
            title: 'فونت',
            child: Wrap(
              spacing: 12,
              runSpacing: 12,
              children: _fontPresets.map((font) {
                final isSelected = font['value'] == _fontFamily;
                return ChoiceChip(
                  label: Text(font['name']!),
                  selected: isSelected,
                  onSelected: (_) => setState(() => _fontFamily = font['value']!),
                );
              }).toList(),
            ),
          ),

          const SizedBox(height: 20),

          // حالت
          _sectionCard(
            title: 'حالت نمایش',
            child: SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: 'light', label: Text('روشن'), icon: Icon(Icons.light_mode)),
                ButtonSegment(value: 'dark', label: Text('تاریک'), icon: Icon(Icons.dark_mode)),
                ButtonSegment(value: 'auto', label: Text('خودکار'), icon: Icon(Icons.brightness_auto)),
              ],
              selected: {_themeMode},
              onSelectionChanged: (s) => setState(() => _themeMode = s.first),
            ),
          ),

          const SizedBox(height: 20),

          // شعاع گوشه
          _sectionCard(
            title: 'شعاع گوشه‌ها ($_borderRadius px)',
            child: Slider(
              value: _borderRadius.toDouble(),
              min: 0,
              max: 24,
              divisions: 24,
              label: '$_borderRadius px',
              onChanged: (v) => setState(() => _borderRadius = v.round()),
            ),
          ),

          const SizedBox(height: 20),

          // پیش‌نمایش
          _sectionCard(
            title: 'پیش‌نمایش',
            child: Column(
              children: [
                FilledButton(
                  onPressed: () {},
                  child: const Text('دکمه اصلی'),
                ),
                const SizedBox(height: 12),
                OutlinedButton(
                  onPressed: () {},
                  child: const Text('دکمه ثانویه'),
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: _hexToColor(_primaryColor).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(_borderRadius.toDouble()),
                  ),
                  child: Text('نمونه متن با فونت $_fontFamily'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionCard({required String title, required Widget child}) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
            const SizedBox(height: 16),
            child,
          ],
        ),
      ),
    );
  }

  Color _hexToColor(String hex) {
    hex = hex.replaceFirst('#', '');
    if (hex.length == 6) hex = 'FF$hex';
    return Color(int.parse(hex, radix: 16));
  }
}
